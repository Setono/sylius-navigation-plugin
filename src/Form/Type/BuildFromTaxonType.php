<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

use Setono\SyliusNavigationPlugin\Controller\Command\BuildFromTaxonCommand;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * @extends AbstractType<BuildFromTaxonCommand>
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
            ->add('maxDepth', IntegerType::class, [
                'label' => 'setono_sylius_navigation.form.build_from_taxon.max_depth',
                'help' => 'setono_sylius_navigation.form.build_from_taxon.max_depth_help',
                'required' => false,
                'constraints' => [
                    new Positive(),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BuildFromTaxonCommand::class,
        ]);
    }
}
