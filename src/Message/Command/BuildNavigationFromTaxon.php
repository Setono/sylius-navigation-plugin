<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Message\Command;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class BuildNavigationFromTaxon implements AsyncCommandInterface
{
    public readonly int $navigation;

    public readonly int $taxon;

    public function __construct(
        NavigationInterface|int $navigation,
        TaxonInterface|int $taxon,
        public readonly bool $includeRoot,
        public readonly ?int $maxDepth,
    ) {
        if ($navigation instanceof NavigationInterface) {
            $navigation = (int) $navigation->getId();
        }

        if ($taxon instanceof TaxonInterface) {
            $taxon = (int) $taxon->getId();
        }

        $this->navigation = $navigation;
        $this->taxon = $taxon;
    }
}
