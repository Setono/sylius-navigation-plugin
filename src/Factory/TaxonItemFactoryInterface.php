<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;

interface TaxonItemFactoryInterface extends ItemFactoryInterface
{
    public function createNew(): TaxonItemInterface;
}
