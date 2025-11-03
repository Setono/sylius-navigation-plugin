<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Form\Type;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Form\Type\ItemTranslationType;
use Setono\SyliusNavigationPlugin\Form\Type\TaxonItemType;
use Setono\SyliusNavigationPlugin\Model\ItemTranslation;
use Setono\SyliusNavigationPlugin\Model\TaxonItem;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface as SyliusRepositoryInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class TaxonItemTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_submits_valid_data(): void
    {
        $formData = [
            'enabled' => true,
            'translations' => [
                'en_US' => [
                    'label' => 'Category Item',
                ],
            ],
            'taxon' => 'category',
            'channels' => [],
        ];

        $model = new TaxonItem();
        $model->setCurrentLocale('en_US');
        $model->setFallbackLocale('en_US');

        $form = $this->factory->create(TaxonItemType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($model->isEnabled());
        self::assertSame('Category Item', $model->getLabel());
    }

    /**
     * @test
     */
    public function it_has_all_required_fields_including_taxon(): void
    {
        $form = $this->factory->create(TaxonItemType::class);
        $view = $form->createView();

        // Fields from parent ItemType
        self::assertArrayHasKey('translations', $view->children);
        self::assertArrayHasKey('enabled', $view->children);
        self::assertArrayHasKey('channels', $view->children);

        // Field specific to TaxonItemType
        self::assertArrayHasKey('taxon', $view->children);
    }

    /**
     * @test
     */
    public function it_has_correct_block_prefix(): void
    {
        $type = new TaxonItemType(TaxonItem::class, ['setono_sylius_navigation']);

        self::assertSame('setono_sylius_navigation_taxon_item', $type->getBlockPrefix());
    }

    /**
     * @test
     */
    public function it_handles_optional_taxon_field(): void
    {
        $formData = [
            'enabled' => true,
            'translations' => [
                'en_US' => [
                    'label' => 'Item Without Taxon',
                ],
            ],
        ];

        $model = new TaxonItem();
        $model->setCurrentLocale('en_US');
        $model->setFallbackLocale('en_US');

        $form = $this->factory->create(TaxonItemType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertNull($model->getTaxon());
    }

    protected function getExtensions(): array
    {
        $taxonItemType = new TaxonItemType(TaxonItem::class, ['setono_sylius_navigation']);
        $itemTranslationType = new ItemTranslationType(ItemTranslation::class, ['setono_sylius_navigation']);

        // Create mock locale provider for ResourceTranslationsType
        $localeProvider = $this->prophesize(TranslationLocaleProviderInterface::class);
        $localeProvider->getDefinedLocalesCodes()->willReturn(['en_US']);
        $localeProvider->getDefaultLocaleCode()->willReturn('en_US');

        $translationsType = new ResourceTranslationsType($localeProvider->reveal());

        // Create mock repository for ChannelChoiceType
        $channelRepository = $this->prophesize(RepositoryInterface::class);
        $channelRepository->findAll()->willReturn([]);

        $channelType = new ChannelChoiceType($channelRepository->reveal());

        // Create mock repository for ResourceAutocompleteChoiceType
        $taxonRepository = $this->prophesize(SyliusRepositoryInterface::class);

        // Create mock service registry
        $resourceRepositoryRegistry = $this->prophesize(ServiceRegistryInterface::class);
        $resourceRepositoryRegistry->get('sylius.taxon')->willReturn($taxonRepository->reveal());

        // Register both parent and child types for taxon autocomplete
        $parentType = new \Sylius\Bundle\ResourceBundle\Form\Type\ResourceAutocompleteChoiceType($resourceRepositoryRegistry->reveal());
        $taxonType = new TaxonAutocompleteChoiceType();

        return [
            new PreloadedExtension([
                TaxonItemType::class => $taxonItemType,
                ItemTranslationType::class => $itemTranslationType,
                ResourceTranslationsType::class => $translationsType,
                ChannelChoiceType::class => $channelType,
                \Sylius\Bundle\ResourceBundle\Form\Type\ResourceAutocompleteChoiceType::class => $parentType,
                TaxonAutocompleteChoiceType::class => $taxonType,
            ], []),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
