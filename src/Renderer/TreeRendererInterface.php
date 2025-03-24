<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

interface TreeRendererInterface
{
    public function render(ItemInterface $item, ChannelInterface $channel = null, string $localeCode = null): string;
}
