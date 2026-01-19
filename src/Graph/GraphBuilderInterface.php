<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Graph;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

interface GraphBuilderInterface
{
    /**
     * Builds a graph of navigation items for the given channel
     *
     * @return iterable<Node>
     */
    public function build(NavigationInterface $navigation, ChannelInterface $channel): iterable;
}
