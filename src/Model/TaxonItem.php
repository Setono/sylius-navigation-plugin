<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Setono\SyliusNavigationPlugin\Attribute\ItemType;
use Setono\SyliusNavigationPlugin\Form\Type\TaxonItemType;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

#[ItemType(
    name: 'taxon',
    formType: TaxonItemType::class,
    template: '@SetonoSyliusNavigationPlugin/navigation/build/form/_taxon_item.html.twig',
    label: 'Taxon Item',
)]
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

    /**
     * Returns the label from translation if set, otherwise returns the taxon's name
     */
    public function getLabel(): ?string
    {
        $label = parent::getLabel();
        if (null !== $label && '' !== $label) {
            return $label;
        }

        return $this->taxon?->getName();
    }
}
