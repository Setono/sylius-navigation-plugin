<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Stringable;
use Sylius\Component\Channel\Model\ChannelsAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;

interface ItemInterface extends
    ChannelsAwareInterface,
    ResourceInterface,
    Stringable,
    TimestampableInterface,
    ToggleableInterface,
    TranslatableInterface
{
    public function getLabel(): ?string;

    public function setLabel(?string $label): void;
}
