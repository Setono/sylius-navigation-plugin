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
        $manager = $this->getManager($item);

        // Check if self-reference already exists to prevent duplicates
        $existingSelfClosure = $this->closureRepository->findOneBy([
            'ancestor' => $item,
            'descendant' => $item,
            'depth' => 0,
        ]);

        if (null === $existingSelfClosure) {
            $selfClosure = $this->closureFactory->createSelfRelationship($item);
            $manager->persist($selfClosure);
        }

        if (null !== $parent) {
            $ancestorClosures = $this->closureRepository->findAncestors($parent);

            foreach ($ancestorClosures as $ancestorClosure) {
                $ancestor = $ancestorClosure->getAncestor();
                Assert::notNull($ancestor);

                $depth = $ancestorClosure->getDepth() + 1;

                // Check if this closure already exists to prevent duplicates
                $existingClosure = $this->closureRepository->findOneBy([
                    'ancestor' => $ancestor,
                    'descendant' => $item,
                    'depth' => $depth,
                ]);

                if (null === $existingClosure) {
                    $closure = $this->closureFactory->createRelationship($ancestor, $item, $depth);
                    $manager->persist($closure);
                }
            }
        }

        if ($flush) {
            $manager->flush();
        }
    }

    public function removeTree(ItemInterface $root): void
    {
        $manager = $this->getManager($root);

        // Step 1: Collect all item IDs in the subtree (including root and all descendants)
        // The closure table includes self-references (depth=0) for all items
        $qb = $manager->createQueryBuilder();
        $qb->select('IDENTITY(c.descendant)')
            ->from($this->closureRepository->getClassName(), 'c')
            ->where('c.ancestor = :root')
            ->setParameter('root', $root);

        $result = $qb->getQuery()->getResult();
        Assert::isArray($result);

        $itemIds = array_column($result, 1);

        // If no closures found, still need to delete the root item itself
        if (empty($itemIds)) {
            $itemIds = [$root->getId()];
        }

        // Step 2: Delete all closures for items in the subtree
        $qb = $manager->createQueryBuilder();
        $qb->delete($this->closureRepository->getClassName(), 'c')
            ->where('c.descendant IN (:itemIds)')
            ->setParameter('itemIds', $itemIds);

        $qb->getQuery()->execute();

        // Step 3: Delete all items in the subtree
        $qb = $manager->createQueryBuilder();
        $qb->delete(ItemInterface::class, 'i')
            ->where('i.id IN (:itemIds)')
            ->setParameter('itemIds', $itemIds);

        $qb->getQuery()->execute();
    }

    public function moveItem(ItemInterface $item, ItemInterface $newParent = null, int $position = 0): void
    {
        $manager = $this->getManager($item);

        // Capture old position and parent BEFORE making any changes
        $oldPosition = $item->getPosition();
        $oldParent = $this->findParent($item);

        // Remove existing closure relationships for this item
        $existingClosures = $this->closureRepository->findAncestors($item);

        foreach ($existingClosures as $closure) {
            if ($closure->getDepth() > 0) { // Don't remove self-reference
                $manager->remove($closure);
            }
        }

        // Flush removals before creating new relationships to avoid
        // unique constraint violations when an item is moved back to
        // the same parent (or shares ancestor paths with the old position)
        $manager->flush();

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

        // Step 1: Close the gap at the old position (decrement old siblings after old position)
        $this->decrementSiblingPositions($item, $oldParent, $oldPosition);

        // Step 2: Make space at the new position (increment new siblings at/after new position)
        $this->incrementSiblingPositions($item, $newParent, $position);

        // Step 3: Set the item's new position
        $item->setPosition($position);

        $manager->flush();
    }

    /**
     * Find the direct parent of an item using the closure table.
     * Must be called BEFORE removing closures.
     */
    private function findParent(ItemInterface $item): ?ItemInterface
    {
        $parentClosure = $this->closureRepository->findOneBy([
            'descendant' => $item,
            'depth' => 1,
        ]);

        return $parentClosure?->getAncestor();
    }

    /**
     * Close the gap left by removing an item from its old position.
     * Decrements positions of siblings that were after the old position.
     */
    private function decrementSiblingPositions(
        ItemInterface $item,
        ?ItemInterface $oldParent,
        int $oldPosition,
    ): void {
        $navigation = $item->getNavigation();
        if (null === $navigation) {
            return;
        }

        $manager = $this->getManager($item);
        $qb = $manager->createQueryBuilder();
        $qb->update(ItemInterface::class, 'i')
            ->set('i.position', 'i.position - 1')
            ->where('i.navigation = :navigation')
            ->andWhere('i.position > :position')
            ->andWhere('i.id != :itemId')
            ->setParameter('navigation', $navigation)
            ->setParameter('position', $oldPosition)
            ->setParameter('itemId', $item->getId());

        $this->addParentConstraint($qb, $oldParent);

        $qb->getQuery()->execute();
    }

    /**
     * Make space for an item at the new position.
     * Increments positions of siblings at or after the new position.
     */
    private function incrementSiblingPositions(
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

        $this->addParentConstraint($qb, $newParent);

        $qb->getQuery()->execute();
    }

    /**
     * Add parent constraint to a query builder for filtering siblings.
     */
    private function addParentConstraint(
        \Doctrine\ORM\QueryBuilder $qb,
        ?ItemInterface $parent,
    ): void {
        if (null === $parent) {
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
                ->setParameter('parentId', $parent->getId());
        }
    }
}
