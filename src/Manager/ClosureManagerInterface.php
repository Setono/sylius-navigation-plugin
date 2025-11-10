<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Manager;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;

interface ClosureManagerInterface
{
    /**
     * Creates closure relationships for an item.
     *
     * @param bool $flush Whether to flush changes to the database immediately. Set to false for batch operations.
     */
    public function createItem(ItemInterface $item, ItemInterface $parent = null, bool $flush = true): void;

    /**
     * Removes the whole closure tree given a root item
     */
    public function removeTree(ItemInterface $root): void;

    /**
     * Moves an item to a new parent with optional position
     */
    public function moveItem(ItemInterface $item, ItemInterface $newParent = null, int $position = 0): void;
}
