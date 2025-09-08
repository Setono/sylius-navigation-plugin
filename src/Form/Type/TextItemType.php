<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

final class TextItemType extends ItemType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_navigation_text_item';
    }
}
