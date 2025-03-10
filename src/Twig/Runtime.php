<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Twig;

use Setono\SyliusNavigationPlugin\Model\NavigationItemInterface;
use Setono\SyliusNavigationPlugin\Renderer\Item\NavigationItemRendererInterface;
use Setono\SyliusNavigationPlugin\Renderer\NavigationRendererInterface;
use Setono\SyliusNavigationPlugin\Renderer\NavigationTreeRendererInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class Runtime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly NavigationRendererInterface $navigationRenderer,
        private readonly NavigationTreeRendererInterface $navigationTreeRenderer,
        private readonly NavigationItemRendererInterface $navigationItemRenderer,
    ) {
    }

    public function navigation(string $code): string
    {
        return $this->navigationRenderer->render($code);
    }

    public function tree(NavigationItemInterface $item): string
    {
        return $this->navigationTreeRenderer->render($item);
    }

    public function item(NavigationItemInterface $item): string
    {
        return $this->navigationItemRenderer->render($item);
    }
}
