<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractBuilderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Name',
                'required' => true,
                'mapped' => false, // Handle manually in controller since it goes to translation
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Enabled',
                'required' => false,
            ])
            ->add('parent_id', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('item_id', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('type', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
        ;

        $this->buildSpecificForm($builder, $options);
    }

    /**
     * Override this method in child classes to add type-specific fields
     */
    protected function buildSpecificForm(FormBuilderInterface $builder, array $options): void
    {
        // Default implementation does nothing
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false, // For AJAX forms
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
