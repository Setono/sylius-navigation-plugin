<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Repository;

use Setono\SyliusNavigationPlugin\Model\ClosureInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends RepositoryInterface<ClosureInterface>
 */
interface ClosureRepositoryInterface extends RepositoryInterface
{
    /**
     * Will return a list of closures where $item is a descendant
     *
     * @return list<ClosureInterface>
     */
    public function findAncestors(ItemInterface $item): array;

    /**
     * Will return the whole graph for the given $root
     *
     * @return list<ClosureInterface>
     */
    public function findGraph(ItemInterface $root): array;
}
