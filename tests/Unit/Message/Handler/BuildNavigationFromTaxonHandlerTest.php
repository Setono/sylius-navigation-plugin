<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Message\Handler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Setono\SyliusNavigationPlugin\Builder\NavigationBuilderInterface;
use Setono\SyliusNavigationPlugin\Message\Command\BuildNavigationFromTaxon;
use Setono\SyliusNavigationPlugin\Message\Handler\BuildNavigationFromTaxonHandler;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;

final class BuildNavigationFromTaxonHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<NavigationRepositoryInterface> */
    private ObjectProphecy $navigationRepository;

    /** @var ObjectProphecy<TaxonRepositoryInterface> */
    private ObjectProphecy $taxonRepository;

    /** @var ObjectProphecy<NavigationBuilderInterface> */
    private ObjectProphecy $navigationBuilder;

    /** @var ObjectProphecy<LoggerInterface> */
    private ObjectProphecy $logger;

    protected function setUp(): void
    {
        $this->navigationRepository = $this->prophesize(NavigationRepositoryInterface::class);
        $this->taxonRepository = $this->prophesize(TaxonRepositoryInterface::class);
        $this->navigationBuilder = $this->prophesize(NavigationBuilderInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    private function createHandler(?LoggerInterface $logger = null): BuildNavigationFromTaxonHandler
    {
        return new BuildNavigationFromTaxonHandler(
            $this->navigationRepository->reveal(),
            $this->taxonRepository->reveal(),
            $this->navigationBuilder->reveal(),
            $logger,
        );
    }

    /**
     * @test
     */
    public function it_builds_navigation_from_taxon(): void
    {
        $navigation = $this->prophesize(NavigationInterface::class)->reveal();
        $taxon = $this->prophesize(TaxonInterface::class)->reveal();

        $this->navigationRepository->find(1)->willReturn($navigation);
        $this->taxonRepository->find(2)->willReturn($taxon);

        $this->navigationBuilder->buildFromTaxon($navigation, $taxon, true, 3)->shouldBeCalledOnce();
        $this->logger->info(Argument::cetera())->shouldBeCalled();

        $handler = $this->createHandler($this->logger->reveal());
        $handler(new BuildNavigationFromTaxon(1, 2, true, 3));
    }

    /**
     * @test
     */
    public function it_returns_early_when_navigation_is_not_found(): void
    {
        $this->navigationRepository->find(999)->willReturn(null);

        $this->taxonRepository->find(Argument::any())->shouldNotBeCalled();
        $this->navigationBuilder->buildFromTaxon(Argument::cetera())->shouldNotBeCalled();
        $this->logger->warning('Navigation not found', ['navigationId' => 999])->shouldBeCalled();

        $handler = $this->createHandler($this->logger->reveal());
        $handler(new BuildNavigationFromTaxon(999, 1, true, null));
    }

    /**
     * @test
     */
    public function it_returns_early_when_taxon_is_not_found(): void
    {
        $navigation = $this->prophesize(NavigationInterface::class)->reveal();
        $this->navigationRepository->find(1)->willReturn($navigation);
        $this->taxonRepository->find(999)->willReturn(null);

        $this->navigationBuilder->buildFromTaxon(Argument::cetera())->shouldNotBeCalled();
        $this->logger->warning('Taxon not found', ['taxonId' => 999])->shouldBeCalled();

        $handler = $this->createHandler($this->logger->reveal());
        $handler(new BuildNavigationFromTaxon(1, 999, false, null));
    }

    /**
     * @test
     */
    public function it_logs_error_when_builder_throws(): void
    {
        $navigation = $this->prophesize(NavigationInterface::class)->reveal();
        $taxon = $this->prophesize(TaxonInterface::class)->reveal();

        $this->navigationRepository->find(1)->willReturn($navigation);
        $this->taxonRepository->find(2)->willReturn($taxon);

        $exception = new \RuntimeException('Build failed');
        $this->navigationBuilder->buildFromTaxon($navigation, $taxon, true, null)->willThrow($exception);

        $this->logger->error('Failed to build navigation from taxon', Argument::that(function (array $context): bool {
            return $context['navigationId'] === 1
                && $context['taxonId'] === 2
                && $context['exception'] === 'Build failed';
        }))->shouldBeCalled();

        $handler = $this->createHandler($this->logger->reveal());
        $handler(new BuildNavigationFromTaxon(1, 2, true, null));
    }

    /**
     * @test
     */
    public function it_works_without_logger(): void
    {
        $navigation = $this->prophesize(NavigationInterface::class)->reveal();
        $taxon = $this->prophesize(TaxonInterface::class)->reveal();

        $this->navigationRepository->find(1)->willReturn($navigation);
        $this->taxonRepository->find(2)->willReturn($taxon);

        $this->navigationBuilder->buildFromTaxon($navigation, $taxon, false, 5)->shouldBeCalledOnce();

        $handler = $this->createHandler();
        $handler(new BuildNavigationFromTaxon(1, 2, false, 5));
    }

    /**
     * @test
     */
    public function it_does_not_throw_when_navigation_not_found_and_no_logger(): void
    {
        $this->navigationRepository->find(1)->willReturn(null);
        $this->navigationBuilder->buildFromTaxon(Argument::cetera())->shouldNotBeCalled();

        $handler = $this->createHandler();
        $handler(new BuildNavigationFromTaxon(1, 2, true, null));
    }

    /**
     * @test
     */
    public function it_does_not_throw_when_builder_fails_and_no_logger(): void
    {
        $navigation = $this->prophesize(NavigationInterface::class)->reveal();
        $taxon = $this->prophesize(TaxonInterface::class)->reveal();

        $this->navigationRepository->find(1)->willReturn($navigation);
        $this->taxonRepository->find(2)->willReturn($taxon);

        $this->navigationBuilder->buildFromTaxon($navigation, $taxon, true, null)->willThrow(new \RuntimeException('fail'));

        $handler = $this->createHandler();
        $handler(new BuildNavigationFromTaxon(1, 2, true, null));

        // If we reach here, the exception was caught gracefully
        self::assertTrue(true);
    }
}
