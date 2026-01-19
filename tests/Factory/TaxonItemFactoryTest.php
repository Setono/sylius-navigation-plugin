<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Factory\TaxonItemFactory;
use Setono\SyliusNavigationPlugin\Model\TaxonItem;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class TaxonItemFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_creates_new_taxon_item(): void
    {
        $taxonItem = new TaxonItem();

        $decoratedFactory = $this->prophesize(FactoryInterface::class);
        $decoratedFactory->createNew()->willReturn($taxonItem);

        $factory = new TaxonItemFactory($decoratedFactory->reveal());

        self::assertSame($taxonItem, $factory->createNew());
    }

    /**
     * @test
     */
    public function it_creates_taxon_item_from_taxon(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->isEnabled()->willReturn(true);

        $taxonItem = new TaxonItem();

        $decoratedFactory = $this->prophesize(FactoryInterface::class);
        $decoratedFactory->createNew()->willReturn($taxonItem);

        $factory = new TaxonItemFactory($decoratedFactory->reveal());

        $result = $factory->createFromTaxon($taxon->reveal());

        self::assertInstanceOf(TaxonItemInterface::class, $result);
        self::assertSame($taxon->reveal(), $result->getTaxon());
        self::assertTrue($result->isEnabled());
    }

    /**
     * @test
     */
    public function it_creates_disabled_taxon_item_from_disabled_taxon(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->isEnabled()->willReturn(false);

        $taxonItem = new TaxonItem();

        $decoratedFactory = $this->prophesize(FactoryInterface::class);
        $decoratedFactory->createNew()->willReturn($taxonItem);

        $factory = new TaxonItemFactory($decoratedFactory->reveal());

        $result = $factory->createFromTaxon($taxon->reveal());

        self::assertFalse($result->isEnabled());
    }
}
