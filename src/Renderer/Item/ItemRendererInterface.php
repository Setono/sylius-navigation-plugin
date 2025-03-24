<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer\Item;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;

interface ItemRendererInterface
{
    public function render(ItemInterface $item): string;

    public function supports(ItemInterface $item): bool;
}
