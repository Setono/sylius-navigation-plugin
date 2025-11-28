<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

interface NavigationRendererInterface
{
    /**
     * @param NavigationInterface|string $navigation The navigation entity (or code) to render
     * @param string|null $template If set, the renderer will use this template
     * @param ChannelInterface|null $channel If not set, the renderer will use the channel context
     * @param string|null $localeCode If not set, the renderer will use the locale context
     */
    public function render(
        NavigationInterface|string $navigation,
        string $template = null,
        ChannelInterface $channel = null,
        string $localeCode = null,
    ): string;
}
