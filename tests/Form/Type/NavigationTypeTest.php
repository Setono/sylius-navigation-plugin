<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Form\Type;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Form\Type\NavigationType;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

final class NavigationTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_submits_valid_data(): void
    {
        $formData = [
            'code' => 'main_menu',
            'enabled' => true,
            'description' => 'Main navigation menu',
            'channels' => [],
        ];

        $model = new Navigation();
        $form = $this->factory->create(NavigationType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertSame('main_menu', $model->getCode());
        self::assertTrue($model->isEnabled());
        self::assertSame('Main navigation menu', $model->getDescription());
    }

    /**
     * @test
     */
    public function it_has_all_required_fields(): void
    {
        $form = $this->factory->create(NavigationType::class);
        $view = $form->createView();

        self::assertArrayHasKey('code', $view->children);
        self::assertArrayHasKey('enabled', $view->children);
        self::assertArrayHasKey('description', $view->children);
        self::assertArrayHasKey('channels', $view->children);
    }

    /**
     * @test
     */
    public function it_handles_disabled_navigation(): void
    {
        $formData = [
            'code' => 'footer_menu',
            'enabled' => false,
            'description' => 'Footer navigation',
        ];

        $model = new Navigation();
        $form = $this->factory->create(NavigationType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($model->isEnabled());
    }

    /**
     * @test
     */
    public function it_handles_optional_description(): void
    {
        $formData = [
            'code' => 'sidebar',
            'enabled' => true,
        ];

        $model = new Navigation();
        $form = $this->factory->create(NavigationType::class, $model);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertNull($model->getDescription());
    }

    protected function getExtensions(): array
    {
        $navigationType = new NavigationType(Navigation::class, ['setono_sylius_navigation']);

        // Create mock repository for ChannelChoiceType
        $channelRepository = $this->prophesize(RepositoryInterface::class);
        $channelRepository->findAll()->willReturn([]);

        $channelType = new ChannelChoiceType($channelRepository->reveal());

        return [
            new PreloadedExtension([
                NavigationType::class => $navigationType,
                ChannelChoiceType::class => $channelType,
            ], []),
        ];
    }
}
