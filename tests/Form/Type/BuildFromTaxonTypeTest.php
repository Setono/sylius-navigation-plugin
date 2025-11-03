<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Form\Type;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Form\Type\BuildFromTaxonType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceAutocompleteChoiceType;
use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonAutocompleteChoiceType;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface as SyliusRepositoryInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class BuildFromTaxonTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_submits_valid_data(): void
    {
        $formData = [
            'taxon' => 'test_taxon',
            'includeRoot' => true,
        ];

        $form = $this->factory->create(BuildFromTaxonType::class);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        $data = $form->getData();
        self::assertIsArray($data);
        self::assertArrayHasKey('includeRoot', $data);
        self::assertTrue($data['includeRoot']);

        // Note: taxon field is a ResourceAutocompleteChoiceType which expects entity objects
        // In unit tests we can only verify the field exists, not test actual taxon selection
    }

    /**
     * @test
     */
    public function it_has_include_root_defaulting_to_false(): void
    {
        $form = $this->factory->create(BuildFromTaxonType::class);
        $view = $form->createView();

        self::assertArrayHasKey('includeRoot', $view->children);
        self::assertFalse($view->children['includeRoot']->vars['data']);
    }

    /**
     * @test
     */
    public function it_has_all_required_fields(): void
    {
        $form = $this->factory->create(BuildFromTaxonType::class);
        $view = $form->createView();

        self::assertArrayHasKey('taxon', $view->children);
        self::assertArrayHasKey('includeRoot', $view->children);
    }

    /**
     * @test
     */
    public function it_transforms_data_correctly_when_include_root_is_not_provided(): void
    {
        $formData = [
            'taxon' => 'category',
        ];

        $form = $this->factory->create(BuildFromTaxonType::class);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        $data = $form->getData();
        self::assertFalse($data['includeRoot']);
    }

    protected function getExtensions(): array
    {
        // Create mock repository for ResourceAutocompleteChoiceType
        $taxonRepository = $this->prophesize(SyliusRepositoryInterface::class);

        // Create mock service registry
        $resourceRepositoryRegistry = $this->prophesize(ServiceRegistryInterface::class);
        $resourceRepositoryRegistry->get('sylius.taxon')->willReturn($taxonRepository->reveal());

        return [
            new PreloadedExtension([
                // Register both parent and child types
                ResourceAutocompleteChoiceType::class => new ResourceAutocompleteChoiceType($resourceRepositoryRegistry->reveal()),
                TaxonAutocompleteChoiceType::class => new TaxonAutocompleteChoiceType(),
            ], []),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
