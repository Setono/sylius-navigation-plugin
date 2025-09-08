<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

use Setono\SyliusNavigationPlugin\Attribute\NavigationItem;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

#[NavigationItem(name: 'taxon', label: 'Taxon Item', priority: 5)]
final class BuilderTaxonItemType extends AbstractBuilderItemType
{
    protected function buildSpecificForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taxon', TaxonAutocompleteChoiceType::class, [
                'label' => 'Taxon',
                'required' => false,
            ])
            ->add('taxon_id', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
        ;
    }
}