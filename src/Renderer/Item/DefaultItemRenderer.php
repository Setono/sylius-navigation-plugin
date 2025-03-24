<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer\Item;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Twig\Environment;

final class DefaultItemRenderer implements ItemRendererInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function render(ItemInterface $item): string
    {
        return $this->twig->render('@SetonoSyliusNavigationPlugin/navigation/item/default.html.twig', [
            'item' => $item,
        ]);
    }

    public function supports(ItemInterface $item): bool
    {
        return true;
    }
}
