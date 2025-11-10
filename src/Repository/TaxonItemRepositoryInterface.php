<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Repository;

use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

/**
 * @extends RepositoryInterface<TaxonItemInterface>
 */
interface TaxonItemRepositoryInterface extends RepositoryInterface
{
    /**
     * Find all TaxonItems that reference a specific taxon
     *
     * @return array<array-key, TaxonItemInterface>
     */
    public function findByTaxon(TaxonInterface $taxon): array;
}
