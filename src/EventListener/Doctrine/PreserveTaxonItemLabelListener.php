<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\PreRemoveEventArgs;
use Setono\SyliusNavigationPlugin\Model\ItemTranslationInterface;
use Setono\SyliusNavigationPlugin\Repository\TaxonItemRepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class PreserveTaxonItemLabelListener
{
    public function __construct(private readonly TaxonItemRepositoryInterface $taxonItemRepository)
    {
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $taxon = $args->getObject();
        if (!$taxon instanceof TaxonInterface) {
            return;
        }

        foreach ($this->taxonItemRepository->findByTaxon($taxon) as $taxonItem) {
            $taxonItem->setEnabled(false);

            foreach ($taxonItem->getTranslations() as $itemTranslation) {
                if (!$itemTranslation instanceof ItemTranslationInterface) {
                    continue;
                }

                if ($itemTranslation->getLabel() !== null && $itemTranslation->getLabel() !== '') {
                    continue;
                }

                $locale = $itemTranslation->getLocale();
                if (null === $locale) {
                    continue;
                }

                $taxonName = $taxon->getTranslation($locale)->getName();
                if ($taxonName !== null && $taxonName !== '') {
                    $itemTranslation->setLabel($taxonName);
                }
            }
        }
    }
}
