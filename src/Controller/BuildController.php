<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusNavigationPlugin\Manager\ClosureManagerInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Provider\ItemLabelProviderInterface;
use Setono\SyliusNavigationPlugin\Provider\NavigationTreeProviderInterface;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistryInterface;
use Setono\SyliusNavigationPlugin\Renderer\CachedNavigationRenderer;
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

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly NavigationTreeProviderInterface $navigationTreeProvider,
        private readonly ItemLabelProviderInterface $itemLabelProvider,
    ) {
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
        RepositoryInterface $channelRepository,
    ): JsonResponse {
        $nodeId = $request->query->get('id', '#');

        $channel = null;
        $channelId = $request->query->get('channel');
        if (\is_string($channelId) && '' !== $channelId) {
            $channel = $channelRepository->find((int) $channelId);
            if (!$channel instanceof ChannelInterface) {
                $channel = null;
            }
        }

        if ($nodeId === '#') {
            $tree = $this->navigationTreeProvider->getTree($navigation, false, $channel);
        } else {
            $itemManager = $this->getManager($navigation);
            $parentItem = $itemManager->getRepository(ItemInterface::class)->find((int) $nodeId);

            if (!$parentItem instanceof ItemInterface) {
                return new JsonResponse(['error' => 'Parent item not found'], Response::HTTP_NOT_FOUND);
            }

            $tree = $this->navigationTreeProvider->getChildren($parentItem, false, $channel);
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
    ): JsonResponse {
        $searchTerm = $request->query->get('str', $request->query->get('q', ''));
        if (!is_string($searchTerm) || '' === $searchTerm) {
            return new JsonResponse([]);
        }

        return new JsonResponse($this->navigationTreeProvider->searchItems($navigation, $searchTerm));
    }

    public function getItemTypesAction(ItemTypeRegistryInterface $itemTypeRegistry): JsonResponse
    {
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

            $itemId = $request->query->get('itemId');
            if ($itemId !== null) {
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
        ClosureRepositoryInterface $closureRepository,
    ): JsonResponse {
        try {
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

            $form->handleRequest($request);

            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }

                return new JsonResponse(['error' => 'Form validation failed', 'details' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $label = $request->request->get('label');
            if (\is_string($label)) {
                $item->setLabel($label);
            }

            $item->setNavigation($navigation);

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

            if ($parent !== null) {
                $siblings = $closureRepository->findDirectChildren($parent);
            } else {
                $siblings = $closureRepository->findRootItems($navigation);
            }
            $item->setPosition(count($siblings));

            $this->getManager($item)->persist($item);
            $this->getManager($item)->flush();

            $closureManager->createItem($item, $parent);

            return new JsonResponse([
                'success' => true,
                'item' => [
                    'id' => $item->getId(),
                    'label' => $this->itemLabelProvider->getLabel($item),
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
            $itemType = $itemTypeRegistry->getByEntity($item::class);
            $formClass = $itemType->form;
            $form = $formFactory->create($formClass, $item);

            $form->handleRequest($request);

            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }

                return new JsonResponse(['error' => 'Form validation failed', 'details' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $label = $request->request->get('label');
            if (\is_string($label)) {
                $item->setLabel($label);
            }

            if ($item instanceof TaxonItemInterface && $request->request->get('taxon_id')) {
                $taxon = $taxonRepository->find($request->request->get('taxon_id'));
                if ($taxon instanceof TaxonInterface) {
                    $item->setTaxon($taxon);
                }
            }

            $this->getManager($item)->flush();

            return new JsonResponse([
                'success' => true,
                'item' => [
                    'id' => $item->getId(),
                    'label' => $this->itemLabelProvider->getLabel($item),
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
        ?CachedNavigationRenderer $cachedRenderer = null,
    ): JsonResponse {
        try {
            $closureManager->removeTree($item);

            $cachedRenderer?->invalidate($navigation);

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
        ?CachedNavigationRenderer $cachedRenderer = null,
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

            $cachedRenderer?->invalidate($navigation);

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
}
