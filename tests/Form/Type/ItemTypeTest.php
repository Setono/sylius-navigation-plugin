<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Form\Type;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Form\Type\ItemTranslationType;
use Setono\SyliusNavigationPlugin\Form\Type\ItemType;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\ItemTranslation;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

final class ItemTypeTest extends TypeTestCase
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
                    'label' => 'Test Item',
                ],
            ],
            'channels' => [],
        ];

        $model = new Item();
        $model->setCurrentLocale('en_US');
        $model->setFallbackLocale('en_US');

        $form = $this->factory->create(ItemType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($model->isEnabled());
        self::assertSame('Test Item', $model->getLabel());
    }

    /**
     * @test
     */
    public function it_has_all_required_fields(): void
    {
        $form = $this->factory->create(ItemType::class);
        $view = $form->createView();

        self::assertArrayHasKey('translations', $view->children);
        self::assertArrayHasKey('enabled', $view->children);
        self::assertArrayHasKey('channels', $view->children);
    }

    /**
     * @test
     */
    public function it_has_correct_block_prefix(): void
    {
        $type = new ItemType(Item::class, ['setono_sylius_navigation']);

        self::assertSame('setono_sylius_navigation_item', $type->getBlockPrefix());
    }

    /**
     * @test
     */
    public function it_handles_disabled_state(): void
    {
        $formData = [
            'enabled' => false,
            'translations' => [
                'en_US' => [
                    'label' => 'Disabled Item',
                ],
            ],
        ];

        $model = new Item();
        $model->setCurrentLocale('en_US');
        $model->setFallbackLocale('en_US');

        $form = $this->factory->create(ItemType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($model->isEnabled());
    }

    protected function getExtensions(): array
    {
        $itemType = new ItemType(Item::class, ['setono_sylius_navigation']);
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

        return [
            new PreloadedExtension([
                ItemType::class => $itemType,
                ItemTranslationType::class => $itemTranslationType,
                ResourceTranslationsType::class => $translationsType,
                ChannelChoiceType::class => $channelType,
            ], []),
        ];
    }
}
