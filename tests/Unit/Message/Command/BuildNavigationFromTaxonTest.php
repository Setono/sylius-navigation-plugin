<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Message\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Message\Command\AsyncCommandInterface;
use Setono\SyliusNavigationPlugin\Message\Command\BuildNavigationFromTaxon;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class BuildNavigationFromTaxonTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_implements_async_command_interface(): void
    {
        $command = new BuildNavigationFromTaxon(1, 2, true, null);

        self::assertInstanceOf(AsyncCommandInterface::class, $command);
    }

    /**
     * @test
     */
    public function it_accepts_integer_ids(): void
    {
        $command = new BuildNavigationFromTaxon(1, 2, true, 3);

        self::assertSame(1, $command->navigation);
        self::assertSame(2, $command->taxon);
        self::assertTrue($command->includeRoot);
        self::assertSame(3, $command->maxDepth);
    }

    /**
     * @test
     */
    public function it_accepts_null_max_depth(): void
    {
        $command = new BuildNavigationFromTaxon(1, 2, false, null);

        self::assertNull($command->maxDepth);
        self::assertFalse($command->includeRoot);
    }

    /**
     * @test
     */
    public function it_extracts_id_from_navigation_interface(): void
    {
        $navigation = $this->prophesize(NavigationInterface::class);
        $navigation->getId()->willReturn(42);

        $command = new BuildNavigationFromTaxon($navigation->reveal(), 5, true, null);

        self::assertSame(42, $command->navigation);
        self::assertSame(5, $command->taxon);
    }

    /**
     * @test
     */
    public function it_extracts_id_from_taxon_interface(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getId()->willReturn(99);

        $command = new BuildNavigationFromTaxon(1, $taxon->reveal(), false, 5);

        self::assertSame(1, $command->navigation);
        self::assertSame(99, $command->taxon);
    }

    /**
     * @test
     */
    public function it_extracts_ids_from_both_objects(): void
    {
        $navigation = $this->prophesize(NavigationInterface::class);
        $navigation->getId()->willReturn(10);

        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getId()->willReturn(20);

        $command = new BuildNavigationFromTaxon($navigation->reveal(), $taxon->reveal(), true, 2);

        self::assertSame(10, $command->navigation);
        self::assertSame(20, $command->taxon);
        self::assertTrue($command->includeRoot);
        self::assertSame(2, $command->maxDepth);
    }
}
