<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusNavigationPlugin\Factory\ItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Factory\TaxonItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Manager\ClosureManagerInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

final class BuildController extends AbstractController
{
    use ORMTrait;

    public function __construct(
        private readonly NavigationRepositoryInterface $navigationRepository,
        private readonly ItemFactoryInterface $itemFactory,
        private readonly TaxonItemFactoryInterface $taxonItemFactory,
        private readonly ClosureManagerInterface $closureManager,
        private readonly RepositoryInterface $taxonRepository,
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
     */
    public function getTreeAction(int $id): JsonResponse
    {
        $navigation = $this->navigationRepository->find($id);
        if (!$navigation instanceof NavigationInterface) {
            return new JsonResponse(['error' => 'Navigation not found'], Response::HTTP_NOT_FOUND);
        }

        $tree = $this->buildTreeStructure($navigation);
        
        return new JsonResponse($tree);
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

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $item = $this->createItemFromData($data);
            $parentId = $data['parent_id'] ?? null;
            
            $parent = null;
            if ($parentId) {
                $parent = $this->getManager($item)->getRepository(ItemInterface::class)->find($parentId);
            }

            $this->getManager($item)->persist($item);
            $this->getManager($item)->flush();

            $this->closureManager->createItem($item, $parent);

            if (null === $navigation->getRootItem() && null === $parent) {
                $navigation->setRootItem($item);
                $this->getManager($navigation)->flush();
            }

            return new JsonResponse([
                'id' => $item->getId(),
                'label' => $item->getLabel(),
                'type' => $item instanceof TaxonItemInterface ? 'taxon' : 'simple',
                'enabled' => $item->isEnabled(),
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
        $navigation = $this->navigationRepository->find($id);
        if (!$navigation instanceof NavigationInterface) {
            return new JsonResponse(['error' => 'Navigation not found'], Response::HTTP_NOT_FOUND);
        }

        $item = $this->getManager(ItemInterface::class)->getRepository(ItemInterface::class)->find($itemId);
        if (!$item instanceof ItemInterface) {
            return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->updateItemFromData($item, $data);
            $this->getManager($item)->flush();

            return new JsonResponse([
                'id' => $item->getId(),
                'label' => $item->getLabel(),
                'type' => $item instanceof TaxonItemInterface ? 'taxon' : 'simple',
                'enabled' => $item->isEnabled(),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
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

        $item = $this->getManager(ItemInterface::class)->getRepository(ItemInterface::class)->find($itemId);
        if (!$item instanceof ItemInterface) {
            return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            // If this is the root item, clear it from navigation
            if ($navigation->getRootItem() === $item) {
                $navigation->setRootItem(null);
                $this->getManager($navigation)->flush();
            }

            $this->closureManager->removeTree($item);

            return new JsonResponse(['success' => true]);

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
            $itemId = $data['item_id'] ?? null;
            $newParentId = $data['new_parent_id'] ?? null;
            $position = $data['position'] ?? 0;

            $item = $this->getManager(ItemInterface::class)->getRepository(ItemInterface::class)->find($itemId);
            if (!$item instanceof ItemInterface) {
                return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
            }

            $newParent = null;
            if ($newParentId) {
                $newParent = $this->getManager(ItemInterface::class)->getRepository(ItemInterface::class)->find($newParentId);
            }

            $this->closureManager->moveItem($item, $newParent, $position);

            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function buildTreeStructure(NavigationInterface $navigation): array
    {
        $rootItem = $navigation->getRootItem();
        if (null === $rootItem) {
            return [];
        }

        return $this->buildItemTree($rootItem);
    }

    private function buildItemTree(ItemInterface $item): array
    {
        $children = [];
        foreach ($item->getChildren() as $child) {
            $children[] = $this->buildItemTree($child);
        }

        return [
            'id' => $item->getId(),
            'label' => $item->getLabel(),
            'type' => $item instanceof TaxonItemInterface ? 'taxon' : 'simple',
            'enabled' => $item->isEnabled(),
            'taxon_id' => $item instanceof TaxonItemInterface ? $item->getTaxon()?->getId() : null,
            'children' => $children,
        ];
    }

    private function createItemFromData(array $data): ItemInterface
    {
        $type = $data['type'] ?? 'simple';
        
        if ($type === 'taxon') {
            $taxonId = $data['taxon_id'] ?? null;
            if (!$taxonId) {
                throw new \InvalidArgumentException('Taxon ID is required for taxon items');
            }

            $taxon = $this->taxonRepository->find($taxonId);
            if (!$taxon instanceof TaxonInterface) {
                throw new \InvalidArgumentException('Taxon not found');
            }

            $item = $this->taxonItemFactory->createNew();
            $item->setTaxon($taxon);
            $item->setLabel($data['label'] ?? $taxon->getName());
        } else {
            $item = $this->itemFactory->createNew();
            $item->setLabel($data['label'] ?? 'New Item');
        }

        $item->setEnabled($data['enabled'] ?? true);

        return $item;
    }

    private function updateItemFromData(ItemInterface $item, array $data): void
    {
        if (isset($data['label'])) {
            $item->setLabel($data['label']);
        }

        if (isset($data['enabled'])) {
            $item->setEnabled($data['enabled']);
        }

        if ($item instanceof TaxonItemInterface && isset($data['taxon_id'])) {
            $taxon = $this->taxonRepository->find($data['taxon_id']);
            if ($taxon instanceof TaxonInterface) {
                $item->setTaxon($taxon);
            }
        }
    }
}