<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class TaxonItemType extends ItemType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('taxon', TaxonAutocompleteChoiceType::class, [
                'label' => 'setono_sylius_navigation.form.item.taxon',
                'required' => false,
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_navigation_taxon_item';
    }
}
