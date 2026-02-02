<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusNavigationPlugin\Manager\ClosureManagerInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistryInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
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
     *
     * @param RepositoryInterface<ChannelInterface> $channelRepository
     */
    public function buildAction(
        NavigationInterface $navigation,
        RepositoryInterface $channelRepository,
    ): Response {
        return $this->render('@SetonoSyliusNavigationPlugin/navigation/build.html.twig', [
            'navigation' => $navigation,
            'channels' => $channelRepository->findAll(),
        ]);
    }

    /**
     * Load current tree structure as JSON
     * Supports lazy loading: if node id is provided, returns only children of that node
     *
     * @param RepositoryInterface<ChannelInterface> $channelRepository
     */
    public function getTreeAction(
        Request $request,
        NavigationInterface $navigation,
        ClosureRepositoryInterface $closureRepository,
        ItemTypeRegistryInterface $itemTypeRegistry,
        RepositoryInterface $channelRepository,
    ): JsonResponse {
        // Get node ID from request (for lazy loading)
        $nodeId = $request->query->get('id', '#');

        // Resolve optional channel filter
        $channel = null;
        $channelId = $request->query->get('channel');
        if (\is_string($channelId) && '' !== $channelId) {
            $channel = $channelRepository->find((int) $channelId);
            if (!$channel instanceof ChannelInterface) {
                $channel = null;
            }
        }

        if ($nodeId === '#') {
            // Load root level (first level items)
            $tree = $this->buildTreeStructure($navigation, $closureRepository, $itemTypeRegistry, false, $channel); // false = don't load children recursively
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
                $tree[] = $this->buildItemTree($child, $closureRepository, $itemTypeRegistry, false, $channel); // false = don't load children recursively
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

        // Get all root items and search through them
        $rootItems = $closureRepository->findRootItems($navigation);
        $matchingItems = [];

        foreach ($rootItems as $rootItem) {
            $matchingItems = array_merge($matchingItems, $this->searchItemsRecursive($rootItem, $searchTerm, $closureRepository));
        }

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
                // Don't include the item itself (self-reference)
                if ($ancestor && $ancestor !== $item) {
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
        // Get all registered types with label and options
        $itemTypes = array_map(
            static fn ($itemType) => [
                'label' => $itemType->label,
                'options' => $itemType->options,
            ],
            $itemTypeRegistry->all(),
        );

        return new JsonResponse($itemTypes);
    }

    /**
     * Get form HTML for a specific item type (AJAX endpoint)
     */
    public function getFormAction(
        Request $request,
        string $type,
        ItemTypeRegistryInterface $itemTypeRegistry,
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
                $item = $itemType->factory->createNew();
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
        FormFactoryInterface $formFactory,
        RepositoryInterface $taxonRepository,
        ClosureManagerInterface $closureManager,
    ): JsonResponse {
        try {
            // Determine item type from form data
            $type = $request->request->get('type');

            if (!\is_string($type)) {
                return new JsonResponse(['error' => 'Invalid item type'], Response::HTTP_BAD_REQUEST);
            }

            if (!$itemTypeRegistry->has($type)) {
                return new JsonResponse(['error' => sprintf('Unknown item type: %s', $type)], Response::HTTP_BAD_REQUEST);
            }

            $itemType = $itemTypeRegistry->get($type);
            $item = $itemType->factory->createNew();
            $form = $formFactory->create($itemType->form, $item);

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

            // Set navigation on the item
            $item->setNavigation($navigation);

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
            }

            $this->getManager($item)->persist($item);
            $this->getManager($item)->flush();

            $closureManager->createItem($item, $parent);

            // Return minimal data - let jsTree refresh itself
            return new JsonResponse([
                'success' => true,
                'item' => [
                    'id' => $item->getId(),
                    'label' => $this->getItemLabel($item),
                    'type' => $itemTypeRegistry->getByEntity($item::class)->name,
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
        ItemInterface $item,
        ItemTypeRegistryInterface $itemTypeRegistry,
        FormFactoryInterface $formFactory,
        RepositoryInterface $taxonRepository,
    ): JsonResponse {
        try {
            // Get the appropriate form type from registry based on item entity class
            $itemType = $itemTypeRegistry->getByEntity($item::class);
            $formClass = $itemType->form;
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

            $this->getManager($item)->flush();

            // Return minimal data - let jsTree refresh itself
            return new JsonResponse([
                'success' => true,
                'item' => [
                    'id' => $item->getId(),
                    'label' => $this->getItemLabel($item),
                    'type' => $itemTypeRegistry->getByEntity($item::class)->name,
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
        ItemInterface $item,
        ClosureManagerInterface $closureManager,
    ): JsonResponse {
        try {
            $closureManager->removeTree($item);

            // Return minimal data - let jsTree refresh itself
            return new JsonResponse([
                'success' => true,
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
     * Get detailed information about a specific item
     */
    public function getItemInfoAction(
        NavigationInterface $navigation,
        ItemInterface $item,
        Environment $twig,
    ): Response {
        try {
            $html = $twig->render('@SetonoSyliusNavigationPlugin/navigation/build/_item_info.html.twig', [
                'item' => $item,
                'navigation' => $navigation,
            ]);

            return new JsonResponse(['html' => $html]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function buildTreeStructure(
        NavigationInterface $navigation,
        ClosureRepositoryInterface $closureRepository,
        ItemTypeRegistryInterface $itemTypeRegistry,
        bool $recursive = true,
        ?ChannelInterface $channel = null,
    ): array {
        // Get root items (items with no parent)
        $rootItems = $closureRepository->findRootItems($navigation);
        $childrenArray = [];

        foreach ($rootItems as $rootItem) {
            $childrenArray[] = $this->buildItemTree($rootItem, $closureRepository, $itemTypeRegistry, $recursive, $channel);
        }

        return $childrenArray;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildItemTree(
        ItemInterface $item,
        ClosureRepositoryInterface $closureRepository,
        ItemTypeRegistryInterface $itemTypeRegistry,
        bool $recursive = true,
        ?ChannelInterface $channel = null,
    ): array {
        $children = $this->getDirectChildren($item, $closureRepository);
        $hasChildren = count($children) > 0;
        $childrenArray = [];

        if ($recursive) {
            // Load all children recursively
            foreach ($children as $child) {
                $childrenArray[] = $this->buildItemTree($child, $closureRepository, $itemTypeRegistry, true, $channel);
            }
        }

        // Determine the actual item type for form selection
        $itemType = $itemTypeRegistry->getByEntity($item::class);

        $isChannelHidden = $channel !== null && !$this->isItemVisibleOnChannel($item, $channel);

        $liClasses = [];
        if (!$item->isEnabled()) {
            $liClasses[] = 'item-disabled';
        }
        if ($isChannelHidden) {
            $liClasses[] = 'item-channel-hidden';
        }

        // jsTree-compatible format
        $node = [
            'id' => (string) $item->getId(), // jsTree expects string IDs
            'text' => $this->getItemLabel($item), // jsTree uses 'text' instead of 'label'
            'type' => $itemType->name, // jsTree types for icons
            'state' => [
                'opened' => false, // Don't auto-expand for lazy loading
            ],
            'li_attr' => [
                'class' => implode(' ', $liClasses),
            ],
            'a_attr' => [
                'data-enabled' => $item->isEnabled() ? 'true' : 'false', // Custom attribute for enabled status
                'data-item-type' => $itemType->name, // Store actual item type for edit forms
            ],
            'data' => [ // Custom data for our application
                'enabled' => $item->isEnabled(),
                'taxon_id' => $item instanceof TaxonItemInterface ? $item->getTaxon()?->getId() : null,
                'item_type' => $itemType->name, // Store actual item type
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

    /**
     * Check if an item is visible on a given channel.
     * Empty channels means visible on all channels.
     * Does NOT check isEnabled() since the builder needs to show disabled items.
     */
    private function isItemVisibleOnChannel(ItemInterface $item, ChannelInterface $channel): bool
    {
        return $item->getChannels()->isEmpty() || $item->hasChannel($channel);
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

        // Sort children by position
        usort($children, static fn (ItemInterface $a, ItemInterface $b) => $a->getPosition() <=> $b->getPosition());

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
