<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\ItemTranslation;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Provider\ItemLabelProvider;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;

final class ItemLabelProviderTest extends TestCase
{
    use ProphecyTrait;

    private ItemLabelProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ItemLabelProvider();
    }

    /**
     * @test
     */
    public function it_returns_the_item_label_when_no_locale_is_given(): void
    {
        $item = $this->prophesize(ItemInterface::class);
        $item->getLabel()->willReturn('Home');

        self::assertSame('Home', $this->provider->getLabel($item->reveal()));
    }

    /**
     * @test
     */
    public function it_returns_null_when_item_has_no_label_and_no_locale(): void
    {
        $item = $this->prophesize(ItemInterface::class);
        $item->getLabel()->willReturn(null);

        self::assertNull($this->provider->getLabel($item->reveal()));
    }

    /**
     * @test
     */
    public function it_returns_the_translated_label_when_locale_is_given(): void
    {
        $translation = new ItemTranslation();
        $translation->setLabel('Accueil');

        $item = $this->prophesize(ItemInterface::class);
        $item->getTranslation('fr_FR')->willReturn($translation);

        self::assertSame('Accueil', $this->provider->getLabel($item->reveal(), 'fr_FR'));
    }

    /**
     * @test
     */
    public function it_falls_back_to_taxon_translation_when_item_label_is_empty(): void
    {
        $translation = new ItemTranslation();
        $translation->setLabel('');

        $taxonTranslation = $this->prophesize(TaxonTranslationInterface::class);
        $taxonTranslation->getName()->willReturn('Catégories');

        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getTranslation('fr_FR')->willReturn($taxonTranslation->reveal());

        $item = $this->prophesize(TaxonItemInterface::class);
        $item->willImplement(ItemInterface::class);
        $item->getTranslation('fr_FR')->willReturn($translation);
        $item->getTaxon()->willReturn($taxon->reveal());

        self::assertSame('Catégories', $this->provider->getLabel($item->reveal(), 'fr_FR'));
    }

    /**
     * @test
     */
    public function it_returns_null_when_translated_label_is_null_for_non_taxon_item(): void
    {
        $translation = new ItemTranslation();
        $translation->setLabel(null);

        $item = $this->prophesize(ItemInterface::class);
        $item->getTranslation('fr_FR')->willReturn($translation);

        self::assertNull($this->provider->getLabel($item->reveal(), 'fr_FR'));
    }

    /**
     * @test
     */
    public function it_returns_null_when_taxon_item_has_no_taxon(): void
    {
        $translation = new ItemTranslation();
        $translation->setLabel(null);

        $item = $this->prophesize(TaxonItemInterface::class);
        $item->willImplement(ItemInterface::class);
        $item->getTranslation('fr_FR')->willReturn($translation);
        $item->getTaxon()->willReturn(null);

        self::assertNull($this->provider->getLabel($item->reveal(), 'fr_FR'));
    }
}
