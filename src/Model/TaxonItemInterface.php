<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Sylius\Component\Taxonomy\Model\TaxonInterface;

interface TaxonItemInterface extends ItemInterface
{
    public function getTaxon(): ?TaxonInterface;

    public function setTaxon(?TaxonInterface $taxon): void;
}
