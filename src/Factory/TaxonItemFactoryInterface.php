<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

interface TaxonItemFactoryInterface extends FactoryInterface
{
    public function createNew(): TaxonItemInterface;
}
