<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Message\Handler;

use Psr\Log\LoggerInterface;
use Setono\SyliusNavigationPlugin\Builder\NavigationBuilderInterface;
use Setono\SyliusNavigationPlugin\Message\Command\BuildNavigationFromTaxon;
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final class BuildNavigationFromTaxonHandler
{
    public function __construct(
        private readonly NavigationRepositoryInterface $navigationRepository,
        private readonly TaxonRepositoryInterface $taxonRepository,
        private readonly NavigationBuilderInterface $navigationBuilder,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(BuildNavigationFromTaxon $command): void
    {
        $navigation = $this->navigationRepository->find($command->navigation);
        if (null === $navigation) {
            $this->logger?->warning('Navigation not found', [
                'navigationId' => $command->navigation,
            ]);

            return;
        }

        $taxon = $this->taxonRepository->find($command->taxon);
        if (null === $taxon) {
            $this->logger?->warning('Taxon not found', [
                'taxonId' => $command->taxon,
            ]);

            return;
        }

        Assert::isInstanceOf($taxon, TaxonInterface::class);

        try {
            $this->navigationBuilder->buildFromTaxon(
                $navigation,
                $taxon,
                $command->includeRoot,
                $command->maxDepth,
            );

            $this->logger?->info('Navigation build completed successfully', [
                'navigationId' => $command->navigation,
            ]);
        } catch (\Throwable $e) {
            $this->logger?->error('Failed to build navigation from taxon', [
                'navigationId' => $command->navigation,
                'taxonId' => $command->taxon,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
