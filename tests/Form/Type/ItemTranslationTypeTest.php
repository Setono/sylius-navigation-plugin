<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Form\Type;

use Setono\SyliusNavigationPlugin\Form\Type\ItemTranslationType;
use Setono\SyliusNavigationPlugin\Model\ItemTranslation;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

final class ItemTranslationTypeTest extends TypeTestCase
{
    /**
     * @test
     */
    public function it_submits_valid_data(): void
    {
        $formData = [
            'label' => 'Test Navigation Item',
        ];

        $model = new ItemTranslation();
        $form = $this->factory->create(ItemTranslationType::class, $model);

        $expected = new ItemTranslation();
        $expected->setLabel('Test Navigation Item');

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected->getLabel(), $model->getLabel());
    }

    /**
     * @test
     */
    public function it_has_valid_form_view(): void
    {
        $form = $this->factory->create(ItemTranslationType::class);
        $view = $form->createView();

        self::assertArrayHasKey('label', $view->children);
    }

    /**
     * @test
     */
    public function it_has_correct_block_prefix(): void
    {
        $form = $this->factory->create(ItemTranslationType::class);
        $config = $form->getConfig();

        self::assertSame('setono_sylius_navigation_item_translation', $config->getType()->getBlockPrefix());
    }

    /**
     * @test
     */
    public function it_has_optional_label_field(): void
    {
        $form = $this->factory->create(ItemTranslationType::class);

        self::assertFalse($form->get('label')->isRequired());
    }

    protected function getExtensions(): array
    {
        $type = new ItemTranslationType(ItemTranslation::class, ['setono_sylius_navigation']);

        return [
            new PreloadedExtension([
                ItemTranslationType::class => $type,
            ], []),
        ];
    }
}
