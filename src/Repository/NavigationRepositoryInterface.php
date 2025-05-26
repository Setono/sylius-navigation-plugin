<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Repository;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends RepositoryInterface<NavigationInterface>
 */
interface NavigationRepositoryInterface extends RepositoryInterface
{
    public function findOneEnabledByCode(string $code, ChannelInterface $channel = null): ?NavigationInterface;
}
