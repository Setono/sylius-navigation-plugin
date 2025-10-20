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

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Main build page - displays the interactive tree builder interface
     */
    public function buildAction(NavigationInterface $navigation): Response
    {
        return $this->render('@SetonoSyliusNavigationPlugin/navigation/build.html.twig', [
            'navigation' => $navigation,
        ]);
    }

    /**
     * Load current tree structure as JSON
     * Supports lazy loading: if node id is provided, returns only children of that node
     */
    public function getTreeAction(
        Request $request,
        NavigationInterface $navigation,
        ClosureRepositoryInterface $closureRepository,
    ): JsonResponse {
        // Get node ID from request (for lazy loading)
        $nodeId = $request->query->get('id', '#');

        if ($nodeId === '#') {
            // Load root level (first level items)
            $tree = $this->buildTreeStructure($navigation, $closureRepository, false); // false = don't load children recursively
        } else {
            // Load children of specific node
            $itemManager = $this->getManager($navigation);
            $parentItem = $itemManager->getRepository(ItemInterface::class)->find((int) $nodeId);

            if (!$parentItem instanceof ItemInterface) {
                return new JsonResponse(['error' => 'Parent item not found'], Response::HTTP_NOT_FOUND);
            }

            $children = $this->getDirectChildren($parentItem, $closureRepository);
            $tree = [];
            foreach ($children as $child) {
                $tree[] = $this->buildItemTree($child, $closureRepository, false); // false = don't load children recursively
            }
        }

        return new JsonResponse($tree);
    }

    /**
     * Search navigation items by label (jsTree AJAX search)
     * Returns array of node IDs that match the search term
     */
    public function searchItemsAction(
        Request $request,
        NavigationInterface $navigation,
        ClosureRepositoryInterface $closureRepository,
    ): JsonResponse {
        // jsTree sends 'str' by default, but also support 'q' for compatibility
        $searchTerm = $request->query->get('str', $request->query->get('q', ''));
        if (!is_string($searchTerm) || '' === $searchTerm) {
            return new JsonResponse([]);
        }

        $rootItem = $navigation->getRootItem();
        if (!$rootItem) {
            return new JsonResponse([]);
        }

        $matchingItems = $this->searchItemsRecursive($rootItem, $searchTerm, $closureRepository);

        // jsTree expects an array of node IDs (as strings)
        // We need to include both the matched nodes AND all their parent nodes
        // so jsTree can load and expand the tree to show the results
        $nodeIds = [];
        foreach ($matchingItems as $item) {
            // Add the matched node
            $nodeIds[] = (string) $item->getId();

            // Add all parent nodes up to the root using closure table
            // Find closures where this item is the descendant (these give us ancestors/parents)
            $parentClosures = $closureRepository->findBy([
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

    public function getItemTypesAction(ItemTypeRegistryInterface $itemTypeRegistry): JsonResponse
    {
        // Get all registered types and extract name => label mapping
        $itemTypes = array_map(static fn ($itemType) => $itemType->label, $itemTypeRegistry->all());

        return new JsonResponse($itemTypes);
    }

    /**
     * Get form HTML for a specific item type (AJAX endpoint)
     */
    public function getFormAction(
        Request $request,
        string $type,
        ItemTypeRegistryInterface $itemTypeRegistry,
        TaxonItemFactoryInterface $taxonItemFactory,
        TextItemFactoryInterface $textItemFactory,
        ItemFactoryInterface $itemFactory,
        FormFactoryInterface $formFactory,
        Environment $twig,
        NavigationRepositoryInterface $navigationRepository,
    ): Response {
        try {
            if (!$itemTypeRegistry->has($type)) {
                return new JsonResponse(['error' => sprintf('Unknown item type: %s', $type)], Response::HTTP_NOT_FOUND);
            }

            $itemType = $itemTypeRegistry->get($type);
            $formClass = $itemType->form;

            // Check if we're editing an existing item
            $itemId = $request->query->get('itemId');
            if ($itemId !== null) {
                // Load existing item for editing
                // We need a navigation to get the manager, so get any navigation (they all share the same manager)
                $anyNavigation = $navigationRepository->findOneBy([]);
                if (!$anyNavigation instanceof NavigationInterface) {
                    return new JsonResponse(['error' => 'No navigation found in the system'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                $item = $this->getManager($anyNavigation)->getRepository(ItemInterface::class)->find((int) $itemId);
                if (!$item instanceof ItemInterface) {
                    return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
                }
            } else {
                // Create new item instance
                $item = match ($type) {
                    'taxon' => $taxonItemFactory->createNew(),
                    'text' => $textItemFactory->createNew(),
                    default => $itemFactory->createNew(),
                };
            }

            $form = $formFactory->create($formClass, $item);

            $html = $twig->render($itemType->template, [
                'form' => $form->createView(),
                'type' => $type,
                'metadata' => $itemType,
            ]);

            return new JsonResponse(['html' => $html]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Add new item to navigation
     *
     * @param RepositoryInterface<TaxonInterface> $taxonRepository
     */
    public function addItemAction(
        Request $request,
        NavigationInterface $navigation,
        ItemTypeRegistryInterface $itemTypeRegistry,
        TaxonItemFactoryInterface $taxonItemFactory,
        TextItemFactoryInterface $textItemFactory,
        ItemFactoryInterface $itemFactory,
        FormFactoryInterface $formFactory,
        RepositoryInterface $taxonRepository,
        ClosureManagerInterface $closureManager,
        ClosureRepositoryInterface $closureRepository,
        Environment $twig,
    ): JsonResponse {
        try {
            // Determine item type from form data
            $type = $request->request->get('type', 'text');

            if (!\is_string($type)) {
                return new JsonResponse(['error' => 'Invalid item type'], Response::HTTP_BAD_REQUEST);
            }

            if (!$itemTypeRegistry->has($type)) {
                return new JsonResponse(['error' => sprintf('Unknown item type: %s', $type)], Response::HTTP_BAD_REQUEST);
            }

            // Create item based on type
            $item = match ($type) {
                'taxon' => $taxonItemFactory->createNew(),
                'text' => $textItemFactory->createNew(),
                default => $itemFactory->createNew(),
            };

            // Get the appropriate form type from registry
            $formClass = $itemTypeRegistry->get($type)->form;
            $form = $formFactory->create($formClass, $item);

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
            $label = $request->request->get('label');
            if (\is_string($label)) {
                $item->setLabel($label);
            }

            // Handle taxon_id for TaxonItem (since it's unmapped)
            if ($item instanceof TaxonItemInterface && $request->request->get('taxon_id')) {
                $taxon = $taxonRepository->find($request->request->get('taxon_id'));
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

            $closureManager->createItem($item, $parent);

            // Return rendered tree HTML
            $tree = $this->buildTreeStructureEntities($navigation, $closureRepository);
            $html = $twig->render('@SetonoSyliusNavigationPlugin/navigation/build/_tree.html.twig', [
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
     *
     * @param RepositoryInterface<TaxonInterface> $taxonRepository
     */
    public function updateItemAction(
        Request $request,
        NavigationInterface $navigation,
        int $itemId,
        ItemTypeRegistryInterface $itemTypeRegistry,
        FormFactoryInterface $formFactory,
        RepositoryInterface $taxonRepository,
        ClosureRepositoryInterface $closureRepository,
        Environment $twig,
    ): JsonResponse {
        try {
            $itemManager = $this->getManager($navigation);
            $item = $itemManager->getRepository(ItemInterface::class)->find($itemId);
            if (!$item instanceof ItemInterface) {
                return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
            }

            // Get the appropriate form type from registry based on item type
            $type = $item instanceof TaxonItemInterface ? 'taxon' : 'text';
            $formClass = $itemTypeRegistry->get($type)->form;
            $form = $formFactory->create($formClass, $item);

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
            $label = $request->request->get('label');
            if (\is_string($label)) {
                $item->setLabel($label);
            }

            // Handle taxon_id for TaxonItem (since it's unmapped)
            if ($item instanceof TaxonItemInterface && $request->request->get('taxon_id')) {
                $taxon = $taxonRepository->find($request->request->get('taxon_id'));
                if ($taxon instanceof TaxonInterface) {
                    $item->setTaxon($taxon);
                }
            }

            $itemManager->flush();

            // Return rendered tree HTML
            $tree = $this->buildTreeStructureEntities($navigation, $closureRepository);
            $html = $twig->render('@SetonoSyliusNavigationPlugin/navigation/build/_tree.html.twig', [
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
    public function deleteItemAction(
        NavigationInterface $navigation,
        int $itemId,
        ClosureManagerInterface $closureManager,
        ClosureRepositoryInterface $closureRepository,
        Environment $twig,
    ): JsonResponse {
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

            $closureManager->removeTree($item);

            // Return rendered tree HTML
            $tree = $this->buildTreeStructureEntities($navigation, $closureRepository);
            $html = $twig->render('@SetonoSyliusNavigationPlugin/navigation/build/_tree.html.twig', [
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
    public function reorderItemAction(
        Request $request,
        NavigationInterface $navigation,
        ClosureManagerInterface $closureManager,
    ): JsonResponse {
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

            $closureManager->moveItem($item, $newParent, $position);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function buildTreeStructure(
        NavigationInterface $navigation,
        ClosureRepositoryInterface $closureRepository,
        bool $recursive = true,
    ): array {
        $rootItem = $navigation->getRootItem();
        if (null === $rootItem) {
            return [];
        }

        // Get direct children of the hidden root (these are the UI "root" items)
        $children = $this->getDirectChildren($rootItem, $closureRepository);
        $childrenArray = [];

        foreach ($children as $child) {
            $childrenArray[] = $this->buildItemTree($child, $closureRepository, $recursive);
        }

        return $childrenArray;
    }

    /**
     * @return array<int, array{entity: ItemInterface, children: array<int, mixed>}>
     */
    private function buildTreeStructureEntities(
        NavigationInterface $navigation,
        ClosureRepositoryInterface $closureRepository,
    ): array {
        $rootItem = $navigation->getRootItem();
        if (null === $rootItem) {
            return [];
        }

        // Get direct children of the hidden root (these are the UI "root" items)
        $children = $this->getDirectChildren($rootItem, $closureRepository);
        $childrenArray = [];

        foreach ($children as $child) {
            $childrenArray[] = $this->buildItemTreeEntities($child, $closureRepository);
        }

        return $childrenArray;
    }

    /**
     * @return array{entity: ItemInterface, children: array<int, mixed>}
     */
    private function buildItemTreeEntities(ItemInterface $item, ClosureRepositoryInterface $closureRepository): array
    {
        $children = $this->getDirectChildren($item, $closureRepository);
        $childrenArray = [];

        foreach ($children as $child) {
            $childrenArray[] = $this->buildItemTreeEntities($child, $closureRepository);
        }

        return [
            'entity' => $item,
            'children' => $childrenArray,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildItemTree(
        ItemInterface $item,
        ClosureRepositoryInterface $closureRepository,
        bool $recursive = true,
    ): array {
        $children = $this->getDirectChildren($item, $closureRepository);
        $hasChildren = count($children) > 0;
        $childrenArray = [];

        if ($recursive) {
            // Load all children recursively
            foreach ($children as $child) {
                $childrenArray[] = $this->buildItemTree($child, $closureRepository, true);
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
    private function getDirectChildren(ItemInterface $item, ClosureRepositoryInterface $closureRepository): array
    {
        // Find all closures where this item is the ancestor with depth = 1 (direct children)
        $childClosures = $closureRepository->findBy([
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
    private function searchItemsRecursive(
        ItemInterface $item,
        string $searchTerm,
        ClosureRepositoryInterface $closureRepository,
    ): array {
        $matches = [];
        $children = $this->getDirectChildren($item, $closureRepository);

        foreach ($children as $child) {
            $label = $this->getItemLabel($child);
            if ($label && stripos($label, $searchTerm) !== false) {
                $matches[] = $child;
            }

            // Search in children recursively
            $childMatches = $this->searchItemsRecursive($child, $searchTerm, $closureRepository);
            $matches = array_merge($matches, $childMatches);
        }

        return $matches;
    }
}
