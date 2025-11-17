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

        // Get all siblings (items with same parent) to adjust their positions
        $siblings = $this->getSiblings($item, $newParent);

        // Reorder siblings to make room for the moved item at the specified position
        $currentPos = 0;
        foreach ($siblings as $sibling) {
            if ($sibling === $item) {
                continue; // Skip the item being moved
            }

            if ($currentPos === $position) {
                ++$currentPos; // Skip the position where we're inserting the moved item
            }

            $sibling->setPosition($currentPos);
            ++$currentPos;
        }

        $manager->flush();
    }

    /**
     * Get all sibling items (items with the same parent)
     *
     * @return ItemInterface[]
     */
    private function getSiblings(ItemInterface $item, ?ItemInterface $parent): array
    {
        $navigation = $item->getNavigation();
        if (null === $navigation) {
            return [];
        }

        if (null === $parent) {
            // Get root items for this navigation
            return $this->closureRepository->findRootItems($navigation);
        }

        // Get direct children of the parent
        $childClosures = $this->closureRepository->findBy([
            'ancestor' => $parent,
            'depth' => 1,
        ]);

        $siblings = [];
        foreach ($childClosures as $closure) {
            $descendant = $closure->getDescendant();
            if ($descendant !== null) {
                $siblings[] = $descendant;
            }
        }

        return $siblings;
    }
}
