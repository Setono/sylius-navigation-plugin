<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Form\Type;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Form\Type\ItemTranslationType;
use Setono\SyliusNavigationPlugin\Form\Type\LinkItemType;
use Setono\SyliusNavigationPlugin\Model\ItemTranslation;
use Setono\SyliusNavigationPlugin\Model\LinkItem;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

final class LinkItemTypeTest extends TypeTestCase
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
                    'label' => 'Link Item',
                ],
            ],
            'channels' => [],
            'url' => 'https://example.com',
            'openInNewTab' => true,
            'nofollow' => true,
            'noopener' => false,
            'noreferrer' => false,
        ];

        $model = new LinkItem();
        $model->setCurrentLocale('en_US');
        $model->setFallbackLocale('en_US');

        $form = $this->factory->create(LinkItemType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($model->isEnabled());
        self::assertSame('Link Item', $model->getLabel());
        self::assertSame('https://example.com', $model->getUrl());
        self::assertTrue($model->isOpenInNewTab());
        self::assertTrue($model->isNofollow());
        self::assertFalse($model->isNoopener());
        self::assertFalse($model->isNoreferrer());
    }

    /**
     * @test
     */
    public function it_has_all_required_fields(): void
    {
        $form = $this->factory->create(LinkItemType::class);
        $view = $form->createView();

        // Fields from parent ItemType
        self::assertArrayHasKey('translations', $view->children);
        self::assertArrayHasKey('enabled', $view->children);
        self::assertArrayHasKey('channels', $view->children);

        // Fields specific to LinkItemType
        self::assertArrayHasKey('url', $view->children);
        self::assertArrayHasKey('openInNewTab', $view->children);
        self::assertArrayHasKey('nofollow', $view->children);
        self::assertArrayHasKey('noopener', $view->children);
        self::assertArrayHasKey('noreferrer', $view->children);
    }

    /**
     * @test
     */
    public function it_has_correct_block_prefix(): void
    {
        $type = new LinkItemType(LinkItem::class, ['setono_sylius_navigation']);

        self::assertSame('setono_sylius_navigation_link_item', $type->getBlockPrefix());
    }

    /**
     * @test
     */
    public function it_has_boolean_fields_defaulting_to_false(): void
    {
        $model = new LinkItem();
        $model->setCurrentLocale('en_US');
        $model->setFallbackLocale('en_US');

        $form = $this->factory->create(LinkItemType::class, $model);
        $view = $form->createView();

        self::assertFalse($view->children['openInNewTab']->vars['data']);
        self::assertFalse($view->children['nofollow']->vars['data']);
        self::assertFalse($view->children['noopener']->vars['data']);
        self::assertFalse($view->children['noreferrer']->vars['data']);
    }

    /**
     * @test
     */
    public function it_submits_with_all_link_options_enabled(): void
    {
        $formData = [
            'enabled' => true,
            'translations' => [
                'en_US' => [
                    'label' => 'External Link',
                ],
            ],
            'channels' => [],
            'url' => 'https://external.com',
            'openInNewTab' => true,
            'nofollow' => true,
            'noopener' => true,
            'noreferrer' => true,
        ];

        $model = new LinkItem();
        $model->setCurrentLocale('en_US');
        $model->setFallbackLocale('en_US');

        $form = $this->factory->create(LinkItemType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($model->isOpenInNewTab());
        self::assertTrue($model->isNofollow());
        self::assertTrue($model->isNoopener());
        self::assertTrue($model->isNoreferrer());
    }

    /**
     * @test
     */
    public function it_handles_missing_optional_boolean_fields(): void
    {
        $formData = [
            'enabled' => true,
            'translations' => [
                'en_US' => [
                    'label' => 'Simple Link',
                ],
            ],
            'url' => 'https://example.com',
        ];

        $model = new LinkItem();
        $model->setCurrentLocale('en_US');
        $model->setFallbackLocale('en_US');

        $form = $this->factory->create(LinkItemType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($model->isOpenInNewTab());
        self::assertFalse($model->isNofollow());
        self::assertFalse($model->isNoopener());
        self::assertFalse($model->isNoreferrer());
    }

    protected function getExtensions(): array
    {
        $linkItemType = new LinkItemType(LinkItem::class, ['setono_sylius_navigation']);
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
                LinkItemType::class => $linkItemType,
                ItemTranslationType::class => $itemTranslationType,
                ResourceTranslationsType::class => $translationsType,
                ChannelChoiceType::class => $channelType,
            ], []),
        ];
    }
}
