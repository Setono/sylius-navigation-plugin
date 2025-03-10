<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer;

use Setono\SyliusNavigationPlugin\Model\NavigationItemInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

interface NavigationTreeRendererInterface
{
    public function render(NavigationItemInterface $item, ChannelInterface $channel = null, string $localeCode = null): string;
}
