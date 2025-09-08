<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Setono\SyliusNavigationPlugin\Attribute\ItemType;
use Setono\SyliusNavigationPlugin\Form\Type\BuilderTaxonItemType;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

#[ItemType(name: 'taxon', formType: BuilderTaxonItemType::class, label: 'Taxon Item', priority: 5)]
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
