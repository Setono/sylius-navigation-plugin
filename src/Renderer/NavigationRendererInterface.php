<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

interface NavigationRendererInterface
{
    public function render(
        NavigationInterface|string $navigation,
        ChannelInterface $channel = null,
        string $localeCode = null,
    ): string;
}
