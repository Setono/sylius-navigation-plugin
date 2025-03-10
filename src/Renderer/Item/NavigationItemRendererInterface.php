<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer\Item;

use Setono\SyliusNavigationPlugin\Model\NavigationItemInterface;

interface NavigationItemRendererInterface
{
    public function render(NavigationItemInterface $item): string;

    public function supports(NavigationItemInterface $item): bool;
}
