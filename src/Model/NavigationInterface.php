<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Sylius\Component\Channel\Model\ChannelsAwareInterface;
use Sylius\Component\Resource\Model\CodeAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;

interface NavigationInterface extends
    ChannelsAwareInterface,
    CodeAwareInterface,
    ResourceInterface,
    TimestampableInterface,
    ToggleableInterface
{
    public function getDescription(): ?string;

    public function setDescription(?string $description): void;

    public function getRootItem(): ?ItemInterface;

    public function setRootItem(?ItemInterface $rootItem): void;
}
