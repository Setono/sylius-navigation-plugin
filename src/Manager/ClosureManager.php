<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusNavigationPlugin\Factory\ClosureFactoryInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Webmozart\Assert\Assert;

final class ClosureManager implements ClosureManagerInterface
{
    use ORMTrait;

    public function __construct(
        private readonly ClosureFactoryInterface $closureFactory,
        private readonly ClosureRepositoryInterface $closureRepository,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function createItem(ItemInterface $item, ItemInterface $parent = null, bool $flush = true): void
    {
        $selfClosure = $this->closureFactory->createSelfRelationship($item);

        $manager = $this->getManager($selfClosure);
        $manager->persist($selfClosure);

        if (null !== $parent) {
            $ancestorClosures = $this->closureRepository->findAncestors($parent);

            foreach ($ancestorClosures as $ancestorClosure) {
                $ancestor = $ancestorClosure->getAncestor();
                Assert::notNull($ancestor);

                $closure = $this->closureFactory->createRelationship($ancestor, $item, $ancestorClosure->getDepth() + 1);
                $manager->persist($closure);
            }
        }

        if ($flush) {
            $manager->flush();
        }
    }

    public function removeTree(ItemInterface $root): void
    {
        $closures = $this->closureRepository->findGraph($root);

        if ([] === $closures) {
            return;
        }

        $manager = $this->getManager($closures[0]);

        foreach ($closures as $closure) {
            $ancestor = $closure->getAncestor();
            if (null !== $ancestor) {
                $manager->remove($ancestor);
            }

            $descendant = $closure->getDescendant();
            if (null !== $descendant) {
                $manager->remove($descendant);
            }

            $manager->remove($closure);
        }

        $manager->flush();
    }

    public function moveItem(ItemInterface $item, ItemInterface $newParent = null, int $position = 0): void
    {
        // Remove existing closure relationships for this item
        $existingClosures = $this->closureRepository->findAncestors($item);
        $manager = $this->getManager($item);

        foreach ($existingClosures as $closure) {
            if ($closure->getDepth() > 0) { // Don't remove self-reference
                $manager->remove($closure);
            }
        }

        // Create new relationships with the new parent
        if (null !== $newParent) {
            $ancestorClosures = $this->closureRepository->findAncestors($newParent);

            foreach ($ancestorClosures as $ancestorClosure) {
                $ancestor = $ancestorClosure->getAncestor();
                Assert::notNull($ancestor);

                $closure = $this->closureFactory->createRelationship($ancestor, $item, $ancestorClosure->getDepth() + 1);
                $manager->persist($closure);
            }
        }

        // Update position of the moved item
        $item->setPosition($position);

        // Optimize: Only update positions for affected siblings using bulk SQL update
        // This is much faster than loading all siblings into memory
        $this->updateSiblingPositions($item, $newParent, $position);

        $manager->flush();
    }

    /**
     * Update sibling positions efficiently using bulk SQL update
     * Only updates items at or after the target position
     */
    private function updateSiblingPositions(
        ItemInterface $item,
        ?ItemInterface $newParent,
        int $position,
    ): void {
        $navigation = $item->getNavigation();
        if (null === $navigation) {
            return;
        }

        $manager = $this->getManager($item);
        $qb = $manager->createQueryBuilder();
        $qb->update(ItemInterface::class, 'i')
            ->set('i.position', 'i.position + 1')
            ->where('i.navigation = :navigation')
            ->andWhere('i.position >= :position')
            ->andWhere('i.id != :itemId')
            ->setParameter('navigation', $navigation)
            ->setParameter('position', $position)
            ->setParameter('itemId', $item->getId());

        // Add parent constraint
        if (null === $newParent) {
            // Root items: items that don't have parent closure relationships (depth > 0)
            $qb->andWhere('NOT EXISTS (
                SELECT 1 FROM ' . $this->closureRepository->getClassName() . ' c
                WHERE c.descendant = i.id AND c.depth > 0
            )');
        } else {
            // Children of specific parent: items with closure relationship to parent at depth 1
            $qb->andWhere('EXISTS (
                SELECT 1 FROM ' . $this->closureRepository->getClassName() . ' c
                WHERE c.descendant = i.id
                AND c.ancestor = :parentId
                AND c.depth = 1
            )')
                ->setParameter('parentId', $newParent->getId());
        }

        $qb->getQuery()->execute();
    }
}
