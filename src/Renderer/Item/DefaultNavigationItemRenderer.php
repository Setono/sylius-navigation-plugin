<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer\Item;

use Setono\SyliusNavigationPlugin\Model\NavigationItemInterface;
use Twig\Environment;

final class DefaultNavigationItemRenderer implements NavigationItemRendererInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function render(NavigationItemInterface $item): string
    {
        return $this->twig->render('@SetonoSyliusNavigationPlugin/navigation/item/default.html.twig', [
            'item' => $item,
        ]);
    }

    public function supports(NavigationItemInterface $item): bool
    {
        return true;
    }
}
