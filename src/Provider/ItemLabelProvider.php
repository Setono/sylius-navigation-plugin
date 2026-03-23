<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Provider;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\ItemTranslationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;

final class ItemLabelProvider implements ItemLabelProviderInterface
{
    public function getLabel(ItemInterface $item, ?string $locale = null): ?string
    {
        if (null === $locale) {
            return $item->getLabel();
        }

        $translation = $item->getTranslation($locale);
        $label = $translation instanceof ItemTranslationInterface ? $translation->getLabel() : null;

        if ((null === $label || '' === $label) && $item instanceof TaxonItemInterface) {
            $taxon = $item->getTaxon();
            if (null === $taxon) {
                return null;
            }

            $taxonTranslation = $taxon->getTranslation($locale);

            return $taxonTranslation instanceof TaxonTranslationInterface
                ? $taxonTranslation->getName()
                : $taxon->getName();
        }

        return $label;
    }
}
