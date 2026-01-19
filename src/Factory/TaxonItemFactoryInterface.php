<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

interface TaxonItemFactoryInterface extends ItemFactoryInterface
{
    public function createNew(): TaxonItemInterface;

    public function createFromTaxon(TaxonInterface $taxon): TaxonItemInterface;
}
