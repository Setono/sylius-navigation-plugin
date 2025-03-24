<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Twig;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Renderer\Item\ItemRendererInterface;
use Setono\SyliusNavigationPlugin\Renderer\NavigationRendererInterface;
use Setono\SyliusNavigationPlugin\Renderer\TreeRendererInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class Runtime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly NavigationRendererInterface $navigationRenderer,
        private readonly TreeRendererInterface $navigationTreeRenderer,
        private readonly ItemRendererInterface $navigationItemRenderer,
    ) {
    }

    public function navigation(string $code): string
    {
        return $this->navigationRenderer->render($code);
    }

    public function tree(ItemInterface $item): string
    {
        return $this->navigationTreeRenderer->render($item);
    }

    public function item(ItemInterface $item): string
    {
        return $this->navigationItemRenderer->render($item);
    }
}
