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
    public const STATE_PENDING = 'pending';

    public const STATE_BUILDING = 'building';

    public const STATE_COMPLETED = 'completed';

    public const STATE_FAILED = 'failed';

    public function getDescription(): ?string;

    public function setDescription(?string $description): void;

    public function getState(): string;

    public function setState(string $state): void;
}
