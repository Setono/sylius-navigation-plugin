<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusNavigationPlugin\Factory\ItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Factory\TaxonItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Factory\TextItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Manager\ClosureManagerInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistryInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class BuildController extends AbstractController
{
    use ORMTrait;

    public function __construct(
        private readonly NavigationRepositoryInterface $navigationRepository,
        private readonly ItemFactoryInterface $itemFactory,
        private readonly TaxonItemFactoryInterface $taxonItemFactory,
        private readonly TextItemFactoryInterface $textItemFactory,
        private readonly ClosureManagerInterface $closureManager,
        private readonly ClosureRepositoryInterface $closureRepository,
        private readonly RepositoryInterface $taxonRepository,
        private readonly FormFactoryInterface $formFactory,
        private readonly ItemTypeRegistryInterface $itemTypeRegistry,
        private readonly Environment $twig,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Main build page - displays the interactive tree builder interface
     */
    public function buildAction(int $id): Response
    {
        $navigation = $this->navigationRepository->find($id);
        if (!$navigation instanceof NavigationInterface) {
            $this->addFlash('error', 'setono_sylius_navigation.navigation_not_found');

            return $this->redirectToRoute('setono_sylius_navigation_admin_navigation_index');
        }

        return $this->render('@SetonoSyliusNavigationPlugin/navigation/build.html.twig', [
            'navigation' => $navigation,
        ]);
    }

    /**
     * Load current tree structure as JSON
     * Supports lazy loading: if node id is provided, returns only children of that node
     */
    public function getTreeAction(Request $request, int $id): JsonResponse
    {
        $navigation = $this->navigationRepository->find($id);
        if (!$navigation instanceof NavigationInterface) {
            return new JsonResponse(['error' => 'Navigation not found'], Response::HTTP_NOT_FOUND);
        }

        // Get node ID from request (for lazy loading)
        $nodeId = $request->query->get('id', '#');

        if ($nodeId === '#') {
            // Load root level (first level items)
            $tree = $this->buildTreeStructure($navigation, false); // false = don't load children recursively
        } else {
            // Load children of specific node
            $itemManager = $this->getManager($navigation);
            $parentItem = $itemManager->getRepository(ItemInterface::class)->find((int) $nodeId);

            if (!$parentItem instanceof ItemInterface) {
                return new JsonResponse(['error' => 'Parent item not found'], Response::HTTP_NOT_FOUND);
            }

            $children = $this->getDirectChildren($parentItem);
            $tree = [];
            foreach ($children as $child) {
                $tree[] = $this->buildItemTree($child, false); // false = don't load children recursively
            }
        }

        return new JsonResponse($tree);
    }

    /**
     * Search navigation items by label (jsTree AJAX search)
     * Returns array of node IDs that match the search term
     */
    public function searchItemsAction(Request $request, int $id): JsonResponse
    {
        $navigation = $this->navigationRepository->find($id);
        if (!$navigation instanceof NavigationInterface) {
            return new JsonResponse([]);
        }

        // jsTree sends 'str' by default, but also support 'q' for compatibility
        $searchTerm = $request->query->get('str', $request->query->get('q', ''));
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            return new JsonResponse([]);
        }

        $rootItem = $navigation->getRootItem();
        if (!$rootItem) {
            return new JsonResponse([]);
        }

        $matchingItems = $this->searchItemsRecursive($rootItem, $searchTerm);

        // jsTree expects an array of node IDs (as strings)
        // We need to include both the matched nodes AND all their parent nodes
        // so jsTree can load and expand the tree to show the results
        $nodeIds = [];
        foreach ($matchingItems as $item) {
            // Add the matched node
            $nodeIds[] = (string) $item->getId();

            // Add all parent nodes up to the root using closure table
            // Find closures where this item is the descendant (these give us ancestors/parents)
            $parentClosures = $this->closureRepository->findBy([
                'descendant' => $item,
            ]);

            foreach ($parentClosures as $closure) {
                $ancestor = $closure->getAncestor();
                // Don't include the hidden root or the item itself
                if ($ancestor && $ancestor !== $rootItem && $ancestor !== $item) {
                    $parentId = (string) $ancestor->getId();
                    if (!in_array($parentId, $nodeIds, true)) {
                        $nodeIds[] = $parentId;
                    }
                }
            }
        }

        return new JsonResponse($nodeIds);
    }

    /**
     * Get available item types from registry (AJAX endpoint)
     */
    public function getItemTypesAction(): JsonResponse
    {
        try {
            $itemTypes = $this->itemTypeRegistry->getFormTypesForDropdown();

            return new JsonResponse(['success' => true, 'itemTypes' => $itemTypes]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get form HTML for a specific item type (AJAX endpoint)
     */
    public function getFormAction(Request $request, string $type): Response
    {
        try {
            if (!$this->itemTypeRegistry->has($type)) {
                return new JsonResponse(['error' => sprintf('Unknown item type: %s', $type)], Response::HTTP_NOT_FOUND);
            }

            $formClass = $this->itemTypeRegistry->getForm($type);
            $metadata = $this->itemTypeRegistry->getType($type);

            // Create the appropriate item instance
            $item = match ($type) {
                'taxon' => $this->taxonItemFactory->createNew(),
                'text' => $this->textItemFactory->createNew(),
                default => $this->itemFactory->createNew(),
            };

            $form = $this->formFactory->create($formClass, $item);

            $html = $this->twig->render($metadata['template'], [
                'form' => $form->createView(),
                'type' => $type,
                'metadata' => $metadata,
            ]);

            return new JsonResponse(['success' => true, 'html' => $html]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Add new item to navigation
     */
    public function addItemAction(Request $request, int $id): JsonResponse
    {
        $navigation = $this->navigationRepository->find($id);
        if (!$navigation instanceof NavigationInterface) {
            return new JsonResponse(['error' => 'Navigation not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            // Determine item type from form data
            $type = $request->request->get('type', 'text');

            if (!$this->itemTypeRegistry->has($type)) {
                return new JsonResponse(['error' => sprintf('Unknown item type: %s', $type)], Response::HTTP_BAD_REQUEST);
            }

            // Create item based on type
            $item = match ($type) {
                'taxon' => $this->taxonItemFactory->createNew(),
                'text' => $this->textItemFactory->createNew(),
                default => $this->itemFactory->createNew(),
            };

            // Get the appropriate form type from registry
            $formClass = $this->itemTypeRegistry->getForm($type);
            $form = $this->formFactory->create($formClass, $item);

            // Process the form data using handleRequest
            $form->handleRequest($request);

            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }

                return new JsonResponse(['error' => 'Form validation failed', 'details' => $errors], Response::HTTP_BAD_REQUEST);
            }

            // The form automatically maps data to the entity when using handleRequest

            // Handle label (unmapped field)
            if ($request->request->get('label')) {
                $item->setLabel($request->request->get('label'));
            }

            // Handle taxon_id for TaxonItem (since it's unmapped)
            if ($item instanceof TaxonItemInterface && $request->request->get('taxon_id')) {
                $taxon = $this->taxonRepository->find($request->request->get('taxon_id'));
                if ($taxon instanceof TaxonInterface) {
                    $item->setTaxon($taxon);
                }
            }

            /** @var int|null $parentId */
            $parentId = $request->request->get('parent_id') ? (int) $request->request->get('parent_id') : null;
            $parent = null;
            if ($parentId !== null) {
                $parent = $this->getManager($item)->getRepository(ItemInterface::class)->find($parentId);
            } else {
                // If no parent specified, use the navigation's hidden root item as parent
                $parent = $navigation->getRootItem();
            }

            $this->getManager($item)->persist($item);
            $this->getManager($item)->flush();

            $this->closureManager->createItem($item, $parent);

            // Return rendered tree HTML
            $tree = $this->buildTreeStructureEntities($navigation);
            $html = $this->twig->render('@SetonoSyliusNavigationPlugin/navigation/build/_tree.html.twig', [
                'items' => $tree,
            ]);

            return new JsonResponse([
                'success' => true,
                'html' => $html,
                'item' => [
                    'id' => $item->getId(),
                    'label' => $this->getItemLabel($item),
                    'type' => $item instanceof TaxonItemInterface ? 'taxon' : 'simple',
                    'enabled' => $item->isEnabled(),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Update existing item
     */
    public function updateItemAction(Request $request, int $id, int $itemId): JsonResponse
    {
        try {
            $navigation = $this->navigationRepository->find($id);
            if (!$navigation instanceof NavigationInterface) {
                return new JsonResponse(['error' => 'Navigation not found'], Response::HTTP_NOT_FOUND);
            }

            $itemManager = $this->getManager($navigation);
            $item = $itemManager->getRepository(ItemInterface::class)->find($itemId);
            if (!$item instanceof ItemInterface) {
                return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
            }

            // Get the appropriate form type from registry based on item type
            $type = $item instanceof TaxonItemInterface ? 'taxon' : 'text';
            $formClass = $this->itemTypeRegistry->getForm($type);
            $form = $this->formFactory->create($formClass, $item);

            // Process the form data using handleRequest
            $form->handleRequest($request);

            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }

                return new JsonResponse(['error' => 'Form validation failed', 'details' => $errors], Response::HTTP_BAD_REQUEST);
            }

            // The form automatically maps data to the entity when using handleRequest

            // Handle label (unmapped field)
            if ($request->request->get('label')) {
                $item->setLabel($request->request->get('label'));
            }

            // Handle taxon_id for TaxonItem (since it's unmapped)
            if ($item instanceof TaxonItemInterface && $request->request->get('taxon_id')) {
                $taxon = $this->taxonRepository->find($request->request->get('taxon_id'));
                if ($taxon instanceof TaxonInterface) {
                    $item->setTaxon($taxon);
                }
            }

            $itemManager->flush();

            // Return rendered tree HTML
            $tree = $this->buildTreeStructureEntities($navigation);
            $html = $this->twig->render('@SetonoSyliusNavigationPlugin/navigation/build/_tree.html.twig', [
                'items' => $tree,
            ]);

            return new JsonResponse([
                'success' => true,
                'html' => $html,
                'item' => [
                    'id' => $item->getId(),
                    'label' => $this->getItemLabel($item),
                    'type' => $item instanceof TaxonItemInterface ? 'taxon' : 'simple',
                    'enabled' => $item->isEnabled(),
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Failed to update item: ' . $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove item from navigation
     */
    public function deleteItemAction(int $id, int $itemId): JsonResponse
    {
        $navigation = $this->navigationRepository->find($id);
        if (!$navigation instanceof NavigationInterface) {
            return new JsonResponse(['error' => 'Navigation not found'], Response::HTTP_NOT_FOUND);
        }

        $itemManager = $this->getManager($navigation);
        $item = $itemManager->getRepository(ItemInterface::class)->find($itemId);
        if (!$item instanceof ItemInterface) {
            return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            // Don't allow deletion of the hidden root item
            if ($navigation->getRootItem() === $item) {
                return new JsonResponse(['error' => 'Cannot delete the root item'], Response::HTTP_BAD_REQUEST);
            }

            $this->closureManager->removeTree($item);

            // Return rendered tree HTML
            $tree = $this->buildTreeStructureEntities($navigation);
            $html = $this->twig->render('@SetonoSyliusNavigationPlugin/navigation/build/_tree.html.twig', [
                'items' => $tree,
            ]);

            return new JsonResponse([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Reorder items using drag & drop
     */
    public function reorderItemAction(Request $request, int $id): JsonResponse
    {
        $navigation = $this->navigationRepository->find($id);
        if (!$navigation instanceof NavigationInterface) {
            return new JsonResponse(['error' => 'Navigation not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        try {
            /** @var int|null $itemId */
            $itemId = $data['item_id'] ?? null;
            /** @var int|null $newParentId */
            $newParentId = $data['new_parent_id'] ?? null;
            /** @var int $position */
            $position = $data['position'] ?? 0;

            $itemManager = $this->getManager($navigation);
            $item = $itemManager->getRepository(ItemInterface::class)->find($itemId);
            if (!$item instanceof ItemInterface) {
                return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
            }

            $newParent = null;
            if ($newParentId !== null) {
                $newParent = $itemManager->getRepository(ItemInterface::class)->find($newParentId);
            }

            $this->closureManager->moveItem($item, $newParent, $position);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function buildTreeStructure(NavigationInterface $navigation, bool $recursive = true): array
    {
        $rootItem = $navigation->getRootItem();
        if (null === $rootItem) {
            return [];
        }

        // Get direct children of the hidden root (these are the UI "root" items)
        $children = $this->getDirectChildren($rootItem);
        $childrenArray = [];

        foreach ($children as $child) {
            $childrenArray[] = $this->buildItemTree($child, $recursive);
        }

        return $childrenArray;
    }

    private function buildTreeStructureEntities(NavigationInterface $navigation): array
    {
        $rootItem = $navigation->getRootItem();
        if (null === $rootItem) {
            return [];
        }

        // Get direct children of the hidden root (these are the UI "root" items)
        $children = $this->getDirectChildren($rootItem);
        $childrenArray = [];

        foreach ($children as $child) {
            $childrenArray[] = $this->buildItemTreeEntities($child);
        }

        return $childrenArray;
    }

    private function buildItemTreeEntities(ItemInterface $item): array
    {
        $children = $this->getDirectChildren($item);
        $childrenArray = [];

        foreach ($children as $child) {
            $childrenArray[] = $this->buildItemTreeEntities($child);
        }

        return [
            'entity' => $item,
            'children' => $childrenArray,
        ];
    }

    private function buildItemTree(ItemInterface $item, bool $recursive = true): array
    {
        $children = $this->getDirectChildren($item);
        $hasChildren = count($children) > 0;
        $childrenArray = [];

        if ($recursive) {
            // Load all children recursively
            foreach ($children as $child) {
                $childrenArray[] = $this->buildItemTree($child, true);
            }
        }

        // Determine the actual item type for form selection
        $itemType = $item instanceof TaxonItemInterface ? 'taxon' : 'text';

        // jsTree-compatible format
        $node = [
            'id' => (string) $item->getId(), // jsTree expects string IDs
            'text' => $this->getItemLabel($item), // jsTree uses 'text' instead of 'label'
            'type' => $item instanceof TaxonItemInterface ? 'taxon' : 'default', // jsTree types for icons
            'state' => [
                'opened' => false, // Don't auto-expand for lazy loading
                'disabled' => !$item->isEnabled(), // Disabled state for jsTree
            ],
            'a_attr' => [
                'data-enabled' => $item->isEnabled() ? 'true' : 'false', // Custom attribute for enabled status
                'data-item-type' => $itemType, // Store actual item type for edit forms
            ],
            'data' => [ // Custom data for our application
                'enabled' => $item->isEnabled(),
                'taxon_id' => $item instanceof TaxonItemInterface ? $item->getTaxon()?->getId() : null,
                'item_type' => $itemType, // Store actual item type
            ],
        ];

        if ($recursive) {
            // Include loaded children
            $node['children'] = $childrenArray;
        } else {
            // Mark that this node has children for lazy loading
            $node['children'] = $hasChildren;
        }

        return $node;
    }

    private function getItemLabel(ItemInterface $item): ?string
    {
        return $item->getLabel();
    }

    /**
     * Get direct children (depth = 1) of the given item
     *
     * @return ItemInterface[]
     */
    private function getDirectChildren(ItemInterface $item): array
    {
        // Find all closures where this item is the ancestor with depth = 1 (direct children)
        $childClosures = $this->closureRepository->findBy([
            'ancestor' => $item,
            'depth' => 1,
        ]);

        $children = [];
        foreach ($childClosures as $closure) {
            $descendant = $closure->getDescendant();
            if ($descendant !== null) {
                $children[] = $descendant;
            }
        }

        return $children;
    }

    /**
     * Search items recursively by label
     *
     * @return ItemInterface[]
     */
    private function searchItemsRecursive(ItemInterface $item, string $searchTerm): array
    {
        $matches = [];
        $children = $this->getDirectChildren($item);

        foreach ($children as $child) {
            $label = $this->getItemLabel($child);
            if ($label && stripos($label, $searchTerm) !== false) {
                $matches[] = $child;
            }

            // Search in children recursively
            $childMatches = $this->searchItemsRecursive($child, $searchTerm);
            $matches = array_merge($matches, $childMatches);
        }

        return $matches;
    }
}
