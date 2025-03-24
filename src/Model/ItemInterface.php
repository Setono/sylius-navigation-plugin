<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelsAwareInterface;
use Sylius\Component\Core\Model\PositionAwareInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TimestampableInterface;
use Sylius\Resource\Model\ToggleableInterface;
use Sylius\Resource\Model\TranslatableInterface;

interface ItemInterface extends
    ChannelsAwareInterface,
    PositionAwareInterface,
    ResourceInterface,
    TimestampableInterface,
    ToggleableInterface,
    TranslatableInterface
{
    public function getNavigation(): ?NavigationInterface;

    public function getLabel(): ?string;

    public function setLabel(?string $label): void;

    public function getParent(): ?self;

    /**
     * @return Collection<int, ItemInterface>
     */
    public function getChildren(): Collection;

    public function hasChildren(): bool;

    public function addChild(self $item): void;

    public function removeChild(self $item): void;

    public function hasChild(self $item): bool;

    public function getLevel(): int;
}
