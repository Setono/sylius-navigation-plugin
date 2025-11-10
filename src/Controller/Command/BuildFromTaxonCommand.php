<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Controller\Command;

use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class BuildFromTaxonCommand
{
    public ?TaxonInterface $taxon = null;

    public bool $includeRoot = false;

    public ?int $maxDepth = null;
}
