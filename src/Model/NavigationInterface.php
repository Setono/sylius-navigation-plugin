<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Sylius\Component\Channel\Model\ChannelsAwareInterface;
use Sylius\Resource\Model\CodeAwareInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TimestampableInterface;
use Sylius\Resource\Model\ToggleableInterface;

interface NavigationInterface extends
    ChannelsAwareInterface,
    CodeAwareInterface,
    ResourceInterface,
    TimestampableInterface,
    ToggleableInterface
{
    public function getRootItem(): ?ItemInterface;

    public function setRootItem(?ItemInterface $rootItem): void;

    public function getMaxDepth(): ?int;

    public function setMaxDepth(?int $maxDepth): void;
}
