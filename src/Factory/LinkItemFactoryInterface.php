<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\LinkItemInterface;

interface LinkItemFactoryInterface extends ItemFactoryInterface
{
    public function createNew(): LinkItemInterface;
}
