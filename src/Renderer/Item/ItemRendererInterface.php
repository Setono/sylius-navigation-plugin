<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer\Item;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;

interface ItemRendererInterface
{
    /**
     * @param array<string, scalar|\Stringable> $attributes
     */
    public function render(ItemInterface $item, array $attributes = []): string;

    public function supports(ItemInterface $item): bool;
}
