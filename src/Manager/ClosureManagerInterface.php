<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Manager;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;

interface ClosureManagerInterface
{
    public function createItem(ItemInterface $item, ItemInterface $parent = null): void;

    /**
     * Removes the whole closure tree given a root item
     */
    public function removeTree(ItemInterface $root): void;
}
