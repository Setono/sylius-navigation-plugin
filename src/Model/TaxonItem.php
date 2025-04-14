<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Sylius\Component\Taxonomy\Model\TaxonInterface;

class TaxonItem extends Item implements TaxonItemInterface
{
    protected ?TaxonInterface $taxon = null;

    public function getTaxon(): ?TaxonInterface
    {
        return $this->taxon;
    }

    public function setTaxon(?TaxonInterface $taxon): void
    {
        $this->taxon = $taxon;
    }
}
