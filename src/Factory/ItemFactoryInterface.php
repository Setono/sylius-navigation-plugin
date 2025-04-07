<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

interface ItemFactoryInterface extends FactoryInterface
{
    public function createNew(): ItemInterface;
}
