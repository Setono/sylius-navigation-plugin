<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

final class LinkItemType extends ItemType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('url', UrlType::class, [
                'label' => 'setono_sylius_navigation.form.link_item.url',
                'required' => true,
            ])
            ->add('openInNewTab', CheckboxType::class, [
                'label' => 'setono_sylius_navigation.form.link_item.open_in_new_tab',
                'required' => false,
            ])
            ->add('nofollow', CheckboxType::class, [
                'label' => 'setono_sylius_navigation.form.link_item.nofollow',
                'required' => false,
                'help' => 'setono_sylius_navigation.form.link_item.nofollow_help',
            ])
            ->add('noopener', CheckboxType::class, [
                'label' => 'setono_sylius_navigation.form.link_item.noopener',
                'required' => false,
                'help' => 'setono_sylius_navigation.form.link_item.noopener_help',
            ])
            ->add('noreferrer', CheckboxType::class, [
                'label' => 'setono_sylius_navigation.form.link_item.noreferrer',
                'required' => false,
                'help' => 'setono_sylius_navigation.form.link_item.noreferrer_help',
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_navigation_link_item';
    }
}
