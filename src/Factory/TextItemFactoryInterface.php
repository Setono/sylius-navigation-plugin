<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\TextItemInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

/**
 * @extends FactoryInterface<TextItemInterface>
 */
interface TextItemFactoryInterface extends FactoryInterface
{
    public function createNew(): TextItemInterface;
}
