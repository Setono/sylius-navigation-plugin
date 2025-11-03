<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array{taxon: TaxonInterface, includeRoot: bool}>
 */
final class BuildFromTaxonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('taxon', TaxonAutocompleteChoiceType::class, [
                'label' => 'setono_sylius_navigation.form.build_from_taxon.taxon',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('includeRoot', CheckboxType::class, [
                'label' => 'setono_sylius_navigation.form.build_from_taxon.include_root',
                'required' => false,
                'data' => false,
            ])
        ;
    }
}
