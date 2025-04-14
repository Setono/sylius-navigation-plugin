<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Twig;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Renderer\Item\ItemRendererInterface;
use Setono\SyliusNavigationPlugin\Renderer\NavigationRendererInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class Runtime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly NavigationRendererInterface $navigationRenderer,
        private readonly ItemRendererInterface $navigationItemRenderer,
    ) {
    }

    public function navigation(string $code): string
    {
        return $this->navigationRenderer->render($code);
    }

    /**
     * @param array<string, scalar|\Stringable> $attributes
     */
    public function item(ItemInterface $item, array $attributes = []): string
    {
        return $this->navigationItemRenderer->render($item, $attributes);
    }
}
