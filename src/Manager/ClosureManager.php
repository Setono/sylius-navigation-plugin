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

        // Step 1: Update closure relationships if parent changed
        $oldParent = $this->findParent($item);
        $parentChanged = $oldParent !== $newParent;

        if ($parentChanged) {
            // Remove existing closure relationships (keep self-reference)
            $existingClosures = $this->closureRepository->findAncestors($item);

            foreach ($existingClosures as $closure) {
                if ($closure->getDepth() > 0) {
                    $manager->remove($closure);
                }
            }

            // Flush removals before creating new relationships to avoid
            // unique constraint violations
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

            $manager->flush();
        }

        // Step 2: Reorder siblings by collecting them, inserting at the target position,
        // and assigning contiguous positions. This handles gaps and duplicates correctly.
        $siblings = $this->getSiblings($item, $newParent);

        // Remove the moved item from the list (it may already be absent if parent changed)
        $siblings = array_values(array_filter($siblings, static fn (ItemInterface $sibling) => $sibling->getId() !== $item->getId()));

        // Clamp position to valid range
        $position = max(0, min($position, count($siblings)));

        // Insert at the target position
        array_splice($siblings, $position, 0, [$item]);

        // Assign contiguous positions
        foreach ($siblings as $i => $sibling) {
            $sibling->setPosition($i);
        }

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
     * Get all siblings at the same level, sorted by position.
     *
     * @return ItemInterface[]
     */
    private function getSiblings(ItemInterface $item, ?ItemInterface $parent): array
    {
        $navigation = $item->getNavigation();
        Assert::notNull($navigation);

        if (null === $parent) {
            return $this->closureRepository->findRootItems($navigation);
        }

        $childClosures = $this->closureRepository->findBy([
            'ancestor' => $parent,
            'depth' => 1,
        ]);

        $children = [];
        foreach ($childClosures as $closure) {
            $descendant = $closure->getDescendant();
            if ($descendant !== null) {
                $children[] = $descendant;
            }
        }

        usort($children, static fn (ItemInterface $a, ItemInterface $b) => $a->getPosition() <=> $b->getPosition());

        return $children;
    }
}
