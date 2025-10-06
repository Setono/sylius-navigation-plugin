<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

use Setono\SyliusNavigationPlugin\Model\TextItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class BuilderTextItemType extends AbstractBuilderItemType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('data_class', TextItem::class);
    }
}
