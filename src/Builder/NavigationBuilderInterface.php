<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Builder;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

interface NavigationBuilderInterface
{
    /**
     * Build navigation items from a taxon tree
     *
     * @throws \RuntimeException if the build process fails
     */
    public function buildFromTaxon(
        NavigationInterface $navigation,
        TaxonInterface $taxon,
        bool $includeRoot,
        ?int $maxDepth,
    ): void;
}
