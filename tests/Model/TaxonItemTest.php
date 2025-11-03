<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Model\TaxonItem;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class TaxonItemTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_returns_taxon_name_when_label_is_not_set(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getName()->willReturn('Electronics');

        $item = new TaxonItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setTaxon($taxon->reveal());

        self::assertSame('Electronics', $item->getLabel());
    }

    /**
     * @test
     */
    public function it_returns_taxon_name_when_label_is_empty_string(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getName()->willReturn('Books');

        $item = new TaxonItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setLabel('');
        $item->setTaxon($taxon->reveal());

        self::assertSame('Books', $item->getLabel());
    }

    /**
     * @test
     */
    public function it_returns_custom_label_when_set(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getName()->willReturn('Electronics');

        $item = new TaxonItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setLabel('Custom Electronics Label');
        $item->setTaxon($taxon->reveal());

        self::assertSame('Custom Electronics Label', $item->getLabel());
    }

    /**
     * @test
     */
    public function it_returns_null_when_no_label_and_no_taxon(): void
    {
        $item = new TaxonItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');

        self::assertNull($item->getLabel());
    }

    /**
     * @test
     */
    public function it_returns_null_when_label_is_empty_and_no_taxon(): void
    {
        $item = new TaxonItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setLabel('');

        self::assertNull($item->getLabel());
    }

    /**
     * @test
     */
    public function it_uses_taxon_name_after_unsetting_custom_label(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getName()->willReturn('Clothing');

        $item = new TaxonItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setTaxon($taxon->reveal());
        $item->setLabel('Custom Clothing Label');

        self::assertSame('Custom Clothing Label', $item->getLabel());

        // Clear the label
        $item->setLabel(null);

        self::assertSame('Clothing', $item->getLabel());
    }

    /**
     * @test
     */
    public function it_allows_override_of_taxon_name(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getName()->willReturn('T-Shirts');

        $item = new TaxonItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setTaxon($taxon->reveal());

        // Initially uses taxon name
        self::assertSame('T-Shirts', $item->getLabel());

        // Can override with custom label
        $item->setLabel('All T-Shirts');
        self::assertSame('All T-Shirts', $item->getLabel());
    }

    /**
     * @test
     */
    public function it_returns_correct_label_when_taxon_changes(): void
    {
        $taxon1 = $this->prophesize(TaxonInterface::class);
        $taxon1->getName()->willReturn('Category 1');

        $taxon2 = $this->prophesize(TaxonInterface::class);
        $taxon2->getName()->willReturn('Category 2');

        $item = new TaxonItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setTaxon($taxon1->reveal());

        self::assertSame('Category 1', $item->getLabel());

        // Change taxon
        $item->setTaxon($taxon2->reveal());

        self::assertSame('Category 2', $item->getLabel());
    }

    /**
     * @test
     */
    public function it_converts_to_string_using_label(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getName()->willReturn('Furniture');

        $item = new TaxonItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setTaxon($taxon->reveal());

        self::assertSame('Furniture', (string) $item);
    }

    /**
     * @test
     */
    public function it_handles_null_taxon_after_being_set(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getName()->willReturn('Sports');

        $item = new TaxonItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setTaxon($taxon->reveal());

        self::assertSame('Sports', $item->getLabel());

        // Remove taxon
        $item->setTaxon(null);

        self::assertNull($item->getLabel());
    }
}
