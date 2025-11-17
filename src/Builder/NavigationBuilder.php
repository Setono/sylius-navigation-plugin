<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Builder;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusNavigationPlugin\Factory\TaxonItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Manager\ClosureManagerInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class NavigationBuilder implements NavigationBuilderInterface
{
    use ORMTrait;

    public function __construct(
        private readonly TaxonItemFactoryInterface $taxonItemFactory,
        private readonly ClosureManagerInterface $closureManager,
        private readonly ClosureRepositoryInterface $closureRepository,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function buildFromTaxon(
        NavigationInterface $navigation,
        TaxonInterface $taxon,
        bool $includeRoot,
        ?int $maxDepth,
    ): void {
        // Set state to building
        $navigation->setState(NavigationInterface::STATE_BUILDING);
        $manager = $this->getManager($navigation);
        $manager->flush();

        try {
            // Remove all existing items for this navigation
            $existingItems = $this->closureRepository->findRootItems($navigation);
            foreach ($existingItems as $item) {
                $this->closureManager->removeTree($item);
            }

            // Fetch all descendants in a single query using nested set
            $taxons = $this->fetchDescendants($taxon, $includeRoot, $maxDepth);

            if ([] === $taxons) {
                $navigation->setState(NavigationInterface::STATE_COMPLETED);
                $manager->flush();

                return;
            }

            /** @var \SplObjectStorage<TaxonInterface, \Setono\SyliusNavigationPlugin\Model\ItemInterface> $taxonToItemStorage */
            $taxonToItemStorage = new \SplObjectStorage();

            // Create items in order (parents before children)
            foreach ($taxons as $currentTaxon) {
                $item = $this->createItemFromTaxon($currentTaxon, $navigation);
                $manager->persist($item);
                $manager->flush(); // Flush immediately to get ID for closure queries

                $taxonToItemStorage->attach($currentTaxon, $item);

                // Determine parent item
                $parent = $currentTaxon->getParent();
                $parentItem = null;

                if (null !== $parent && $taxonToItemStorage->contains($parent)) {
                    $parentItem = $taxonToItemStorage[$parent];
                } elseif (!$includeRoot && $parent === $taxon) {
                    // If we're not including root and the parent is the root, this should be a root item (no parent)
                    $parentItem = null;
                }

                $this->closureManager->createItem($item, $parentItem, flush: true);
            }

            // Set state to completed
            $navigation->setState(NavigationInterface::STATE_COMPLETED);
            $manager->flush();
        } catch (\Throwable $e) {
            // Set state to failed and re-throw
            $navigation->setState(NavigationInterface::STATE_FAILED);
            $manager->flush();

            throw new \RuntimeException(
                sprintf('Failed to build navigation from taxon: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * Fetch descendants using nested set (single query)
     *
     * @return list<TaxonInterface>
     */
    private function fetchDescendants(TaxonInterface $root, bool $includeRoot, ?int $maxDepth): array
    {
        $entityManager = $this->getManager($root);
        $qb = $entityManager->createQueryBuilder();

        $qb->select('t')
            ->from($root::class, 't');

        if ($includeRoot) {
            // Include root and all descendants
            $qb->where('t.left >= :left')
                ->andWhere('t.right <= :right')
                ->setParameter('left', $root->getLeft())
                ->setParameter('right', $root->getRight());

            if (null !== $maxDepth) {
                $qb->andWhere('t.level <= :maxLevel')
                    ->setParameter('maxLevel', $root->getLevel() + $maxDepth - 1);
            }
        } else {
            // Exclude root, only descendants
            $qb->where('t.left > :left')
                ->andWhere('t.right < :right')
                ->setParameter('left', $root->getLeft())
                ->setParameter('right', $root->getRight());

            if (null !== $maxDepth) {
                $qb->andWhere('t.level <= :maxLevel')
                    ->setParameter('maxLevel', $root->getLevel() + $maxDepth);
            }
        }

        // Order by left to ensure parents come before children
        $qb->orderBy('t.left', 'ASC');

        /** @var list<TaxonInterface> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    private function createItemFromTaxon(TaxonInterface $taxon, NavigationInterface $navigation): TaxonItemInterface
    {
        $item = $this->taxonItemFactory->createNew();
        $item->setNavigation($navigation);
        $item->setTaxon($taxon);

        return $item;
    }
}
