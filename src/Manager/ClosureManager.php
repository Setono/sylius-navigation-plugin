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

    public function createItem(ItemInterface $item, ItemInterface $parent = null): void
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

        $manager->flush();
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
}
