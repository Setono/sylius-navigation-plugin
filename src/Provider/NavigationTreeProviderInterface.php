<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Provider;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

interface NavigationTreeProviderInterface
{
    /**
     * Returns the full tree structure for a navigation as jsTree-compatible arrays
     *
     * @return list<array<string, mixed>>
     */
    public function getTree(NavigationInterface $navigation, bool $recursive = true, ?ChannelInterface $channel = null): array;

    /**
     * Returns the direct children of an item as jsTree-compatible arrays
     *
     * @return list<array<string, mixed>>
     */
    public function getChildren(ItemInterface $item, bool $recursive = true, ?ChannelInterface $channel = null): array;

    /**
     * Searches items by label and returns matching node IDs including ancestor IDs
     *
     * @return list<string>
     */
    public function searchItems(NavigationInterface $navigation, string $searchTerm): array;
}
