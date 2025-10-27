<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\TextItemInterface;

interface TextItemFactoryInterface extends ItemFactoryInterface
{
    public function createNew(): TextItemInterface;
}
