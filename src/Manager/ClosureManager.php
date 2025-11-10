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
        // For now, implement a simple move by removing existing relationships
        // and recreating them with the new parent

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

        $manager->flush();
    }
}
