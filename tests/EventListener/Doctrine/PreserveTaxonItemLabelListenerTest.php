<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusNavigationPlugin\EventListener\Doctrine\PreserveTaxonItemLabelListener;
use Setono\SyliusNavigationPlugin\Model\ItemTranslation;
use Setono\SyliusNavigationPlugin\Model\TaxonItem;
use Setono\SyliusNavigationPlugin\Repository\TaxonItemRepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;

final class PreserveTaxonItemLabelListenerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<TaxonItemRepositoryInterface> */
    private ObjectProphecy $taxonItemRepository;

    private PreserveTaxonItemLabelListener $listener;

    protected function setUp(): void
    {
        $this->taxonItemRepository = $this->prophesize(TaxonItemRepositoryInterface::class);

        $this->listener = new PreserveTaxonItemLabelListener(
            $this->taxonItemRepository->reveal(),
        );
    }

    /**
     * @test
     */
    public function it_disables_taxon_items_when_the_referenced_taxon_is_removed(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);

        $taxonItem = new TaxonItem();
        $taxonItem->setCurrentLocale('en_US');
        $taxonItem->setFallbackLocale('en_US');
        $taxonItem->setTaxon($taxon->reveal());
        $taxonItem->setEnabled(true);

        $this->taxonItemRepository->findByTaxon($taxon->reveal())->willReturn([$taxonItem]);

        $taxonTranslation = $this->prophesize(TaxonTranslationInterface::class);
        $taxonTranslation->getName()->willReturn('Category');
        $taxon->getTranslation('en_US')->willReturn($taxonTranslation->reveal());

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $args = new PreRemoveEventArgs($taxon->reveal(), $entityManager);

        $this->listener->preRemove($args);

        self::assertFalse($taxonItem->isEnabled());
    }

    /**
     * @test
     */
    public function it_preserves_taxon_name_in_item_translation_when_label_is_empty(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);

        $taxonItem = new TaxonItem();
        $taxonItem->setCurrentLocale('en_US');
        $taxonItem->setFallbackLocale('en_US');
        $taxonItem->setTaxon($taxon->reveal());

        // The translation exists with no label
        $translation = $taxonItem->getTranslation('en_US');
        self::assertInstanceOf(ItemTranslation::class, $translation);
        self::assertNull($translation->getLabel());

        $taxonTranslation = $this->prophesize(TaxonTranslationInterface::class);
        $taxonTranslation->getName()->willReturn('Electronics');
        $taxon->getTranslation('en_US')->willReturn($taxonTranslation->reveal());

        $this->taxonItemRepository->findByTaxon($taxon->reveal())->willReturn([$taxonItem]);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $args = new PreRemoveEventArgs($taxon->reveal(), $entityManager);

        $this->listener->preRemove($args);

        self::assertSame('Electronics', $translation->getLabel());
    }

    /**
     * @test
     */
    public function it_does_not_overwrite_existing_custom_labels(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);

        $taxonItem = new TaxonItem();
        $taxonItem->setCurrentLocale('en_US');
        $taxonItem->setFallbackLocale('en_US');
        $taxonItem->setTaxon($taxon->reveal());
        $taxonItem->setLabel('My Custom Label');

        $translation = $taxonItem->getTranslation('en_US');
        self::assertInstanceOf(ItemTranslation::class, $translation);

        $this->taxonItemRepository->findByTaxon($taxon->reveal())->willReturn([$taxonItem]);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $args = new PreRemoveEventArgs($taxon->reveal(), $entityManager);

        $this->listener->preRemove($args);

        self::assertSame('My Custom Label', $translation->getLabel());
    }

    /**
     * @test
     */
    public function it_does_not_set_label_when_taxon_name_is_null(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);

        $taxonItem = new TaxonItem();
        $taxonItem->setCurrentLocale('en_US');
        $taxonItem->setFallbackLocale('en_US');
        $taxonItem->setTaxon($taxon->reveal());

        $translation = $taxonItem->getTranslation('en_US');
        self::assertInstanceOf(ItemTranslation::class, $translation);

        $taxonTranslation = $this->prophesize(TaxonTranslationInterface::class);
        $taxonTranslation->getName()->willReturn(null);
        $taxon->getTranslation('en_US')->willReturn($taxonTranslation->reveal());

        $this->taxonItemRepository->findByTaxon($taxon->reveal())->willReturn([$taxonItem]);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $args = new PreRemoveEventArgs($taxon->reveal(), $entityManager);

        $this->listener->preRemove($args);

        self::assertNull($translation->getLabel());
    }

    /**
     * @test
     */
    public function it_disables_all_taxon_items_referencing_the_same_taxon(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);

        $taxonItem1 = new TaxonItem();
        $taxonItem1->setCurrentLocale('en_US');
        $taxonItem1->setFallbackLocale('en_US');
        $taxonItem1->setTaxon($taxon->reveal());
        $taxonItem1->setEnabled(true);

        $taxonItem2 = new TaxonItem();
        $taxonItem2->setCurrentLocale('en_US');
        $taxonItem2->setFallbackLocale('en_US');
        $taxonItem2->setTaxon($taxon->reveal());
        $taxonItem2->setEnabled(true);

        $taxonTranslation = $this->prophesize(TaxonTranslationInterface::class);
        $taxonTranslation->getName()->willReturn('Category');
        $taxon->getTranslation('en_US')->willReturn($taxonTranslation->reveal());

        $this->taxonItemRepository->findByTaxon($taxon->reveal())->willReturn([$taxonItem1, $taxonItem2]);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $args = new PreRemoveEventArgs($taxon->reveal(), $entityManager);

        $this->listener->preRemove($args);

        self::assertFalse($taxonItem1->isEnabled());
        self::assertFalse($taxonItem2->isEnabled());
    }

    /**
     * @test
     */
    public function it_preserves_labels_for_multiple_locales(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);

        $taxonItem = new TaxonItem();
        $taxonItem->setCurrentLocale('en_US');
        $taxonItem->setFallbackLocale('en_US');
        $taxonItem->setTaxon($taxon->reveal());

        // Create translations for two locales
        $enTranslation = $taxonItem->getTranslation('en_US');
        self::assertInstanceOf(ItemTranslation::class, $enTranslation);

        $frTranslation = new ItemTranslation();
        $frTranslation->setLocale('fr_FR');
        $taxonItem->addTranslation($frTranslation);

        $enTaxonTranslation = $this->prophesize(TaxonTranslationInterface::class);
        $enTaxonTranslation->getName()->willReturn('Electronics');
        $taxon->getTranslation('en_US')->willReturn($enTaxonTranslation->reveal());

        $frTaxonTranslation = $this->prophesize(TaxonTranslationInterface::class);
        $frTaxonTranslation->getName()->willReturn('Électronique');
        $taxon->getTranslation('fr_FR')->willReturn($frTaxonTranslation->reveal());

        $this->taxonItemRepository->findByTaxon($taxon->reveal())->willReturn([$taxonItem]);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $args = new PreRemoveEventArgs($taxon->reveal(), $entityManager);

        $this->listener->preRemove($args);

        self::assertSame('Electronics', $enTranslation->getLabel());
        self::assertSame('Électronique', $frTranslation->getLabel());
    }

    /**
     * @test
     */
    public function it_ignores_non_taxon_entities(): void
    {
        $unrelatedEntity = new \stdClass();

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $args = new PreRemoveEventArgs($unrelatedEntity, $entityManager);

        $this->taxonItemRepository->findByTaxon()->shouldNotBeCalled();

        $this->listener->preRemove($args);
    }
}
