<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class ItemType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('translations', ResourceTranslationsType::class, [
                'label' => 'setono_sylius_navigation.form.item.translations',
                'entry_type' => ItemTranslationType::class,
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'sylius.ui.enabled',
                'required' => false,
            ])
            ->add('channels', ChannelChoiceType::class, [
                'multiple' => true,
                'expanded' => true,
                'label' => 'setono_sylius_navigation.form.item.channels',
                'required' => false,
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_navigation_item';
    }
}
