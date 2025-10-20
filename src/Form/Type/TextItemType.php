<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

final class TextItemType extends ItemType
{
    public function getBlockPrefix(): string
    {
        return 'setono_sylius_navigation_text_item';
    }
}
