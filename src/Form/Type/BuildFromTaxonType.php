<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array{taxon: TaxonInterface}>
 */
final class BuildFromTaxonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('taxon', TaxonAutocompleteChoiceType::class, [
            'label' => 'setono_sylius_navigation.form.build_from_taxon.taxon',
            'constraints' => [
                new NotBlank(),
            ],
        ]);
    }
}
