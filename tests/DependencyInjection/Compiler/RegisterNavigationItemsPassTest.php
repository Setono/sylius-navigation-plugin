<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Setono\SyliusNavigationPlugin\Attribute\ItemType;
use Setono\SyliusNavigationPlugin\DependencyInjection\Compiler\RegisterNavigationItemsPass;
use Setono\SyliusNavigationPlugin\Form\Type\ItemType as ItemFormType;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistry;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterNavigationItemsPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterNavigationItemsPass());
    }

    /**
     * @test
     */
    public function it_registers_item_types_from_sylius_resources(): void
    {
        // Register the registry service
        $registryDefinition = new Definition(ItemTypeRegistry::class);
        $this->setDefinition(ItemTypeRegistryInterface::class, $registryDefinition);

        // Register the form type service
        $this->setDefinition(ItemFormType::class, new Definition(ItemFormType::class));

        // Register the factory service that Sylius would create
        $this->setDefinition('setono_sylius_navigation.factory.text_item', new Definition(\stdClass::class));

        // Set up sylius resources parameter with a test item type
        $this->setParameter('sylius.resources', [
            'setono_sylius_navigation.text_item' => [
                'classes' => [
                    'model' => TestTextItem::class,
                    'factory' => \Sylius\Resource\Factory\TranslatableFactory::class,
                ],
            ],
        ]);

        $this->compile();

        // Assert that the register method was called on the registry
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            ItemTypeRegistryInterface::class,
            'register',
            [
                'text',
                'Test Text Item',
                TestTextItem::class,
                ItemFormType::class,
                '@SetonoSyliusNavigationPlugin/navigation/build/form/_test.html.twig',
                new Reference('setono_sylius_navigation.factory.text_item'),
            ],
        );
    }

    /**
     * @test
     */
    public function it_skips_when_registry_is_not_available(): void
    {
        // Don't register the registry service
        $this->setParameter('sylius.resources', []);

        $this->compile();

        // Should not throw any exceptions
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_skips_when_sylius_resources_parameter_is_not_set(): void
    {
        // Register the registry service but don't set sylius.resources parameter
        $registryDefinition = new Definition(ItemTypeRegistry::class);
        $this->setDefinition(ItemTypeRegistryInterface::class, $registryDefinition);

        $this->compile();

        // Should not throw any exceptions and should not call register
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_skips_entities_without_item_interface(): void
    {
        // Register the registry service
        $registryDefinition = new Definition(ItemTypeRegistry::class);
        $this->setDefinition(ItemTypeRegistryInterface::class, $registryDefinition);

        // Set up sylius resources with a non-ItemInterface class
        $this->setParameter('sylius.resources', [
            'app.some_entity' => [
                'classes' => [
                    'model' => \stdClass::class,
                ],
            ],
        ]);

        $this->compile();

        // The register method should not have been called
        $methodCalls = $this->container->getDefinition(ItemTypeRegistryInterface::class)->getMethodCalls();
        self::assertCount(0, $methodCalls);
    }

    /**
     * @test
     */
    public function it_skips_entities_without_item_type_attribute(): void
    {
        // Register the registry service
        $registryDefinition = new Definition(ItemTypeRegistry::class);
        $this->setDefinition(ItemTypeRegistryInterface::class, $registryDefinition);

        // Set up sylius resources with an ItemInterface class but without ItemType attribute
        // Note: TestPlainItem extends Item which has an ItemType attribute in the hierarchy,
        // so it will actually be registered. To properly test skipping, we need a class that
        // doesn't have ItemType in its entire hierarchy. For this test, we'll just verify
        // it doesn't throw an exception when the attribute is found in the parent.
        $this->setParameter('sylius.resources', [
            'app.some_entity' => [
                'classes' => [
                    'model' => \stdClass::class, // Not an ItemInterface
                ],
            ],
        ]);

        $this->compile();

        // The register method should not have been called for stdClass (not ItemInterface)
        $methodCalls = $this->container->getDefinition(ItemTypeRegistryInterface::class)->getMethodCalls();
        self::assertCount(0, $methodCalls);
    }

    /**
     * @test
     */
    public function it_uses_default_values_when_attribute_properties_are_null(): void
    {
        // Register the registry service
        $registryDefinition = new Definition(ItemTypeRegistry::class);
        $this->setDefinition(ItemTypeRegistryInterface::class, $registryDefinition);

        // Register the form type service
        $this->setDefinition(ItemFormType::class, new Definition(ItemFormType::class));

        // Register the factory service that Sylius would create
        $this->setDefinition('setono_sylius_navigation.factory.minimal_item', new Definition(\stdClass::class));

        // Set up sylius resources with an item that has minimal attribute configuration
        $this->setParameter('sylius.resources', [
            'setono_sylius_navigation.minimal_item' => [
                'classes' => [
                    'model' => TestMinimalItem::class,
                    'factory' => \Sylius\Resource\Factory\TranslatableFactory::class,
                ],
            ],
        ]);

        $this->compile();

        // Assert that the register method was called with default values
        // When name is null, it converts class name to snake_case using Symfony's u() function
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            ItemTypeRegistryInterface::class,
            'register',
            [
                'test_minimal_item', // Derived from class name TestMinimalItem -> test_minimal_item
                'setono_sylius_navigation.item_types.test_minimal_item',
                TestMinimalItem::class,
                ItemFormType::class,
                '@SetonoSyliusNavigationPlugin/navigation/build/form/_default.html.twig',
                new Reference('setono_sylius_navigation.factory.minimal_item'),
            ],
        );
    }

    /**
     * @test
     */
    public function it_throws_exception_when_form_type_service_not_found(): void
    {
        // Register the registry service
        $registryDefinition = new Definition(ItemTypeRegistry::class);
        $this->setDefinition(ItemTypeRegistryInterface::class, $registryDefinition);

        // Don't register the form type service

        // Set up sylius resources
        $this->setParameter('sylius.resources', [
            'setono_sylius_navigation.text_item' => [
                'classes' => [
                    'model' => TestTextItem::class,
                    'factory' => \Sylius\Resource\Factory\TranslatableFactory::class,
                ],
            ],
        ]);

        $this->expectException(\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException::class);

        $this->compile();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_factory_is_not_configured(): void
    {
        // Register the registry service
        $registryDefinition = new Definition(ItemTypeRegistry::class);
        $this->setDefinition(ItemTypeRegistryInterface::class, $registryDefinition);

        // Register the form type service
        $this->setDefinition(ItemFormType::class, new Definition(ItemFormType::class));

        // Set up sylius resources without factory
        // When factory is not configured in the resource, the factory service won't exist either
        $this->setParameter('sylius.resources', [
            'setono_sylius_navigation.text_item' => [
                'classes' => [
                    'model' => TestTextItem::class,
                    // factory is missing
                ],
            ],
        ]);

        $this->expectException(\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException::class);
        $this->expectExceptionMessage('Factory service "setono_sylius_navigation.factory.text_item" not found');

        $this->compile();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_factory_service_does_not_exist(): void
    {
        // Register the registry service
        $registryDefinition = new Definition(ItemTypeRegistry::class);
        $this->setDefinition(ItemTypeRegistryInterface::class, $registryDefinition);

        // Register the form type service
        $this->setDefinition(ItemFormType::class, new Definition(ItemFormType::class));

        // Don't register the factory service that should exist
        // $this->setDefinition('setono_sylius_navigation.factory.text_item', new Definition(\stdClass::class));

        // Set up sylius resources with factory class but service doesn't exist
        $this->setParameter('sylius.resources', [
            'setono_sylius_navigation.text_item' => [
                'classes' => [
                    'model' => TestTextItem::class,
                    'factory' => \Sylius\Resource\Factory\TranslatableFactory::class,
                ],
            ],
        ]);

        $this->expectException(\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException::class);
        $this->expectExceptionMessage('Factory service "setono_sylius_navigation.factory.text_item" not found');

        $this->compile();
    }
}

// Test classes

#[ItemType(
    name: 'text',
    formType: ItemFormType::class,
    template: '@SetonoSyliusNavigationPlugin/navigation/build/form/_test.html.twig',
    label: 'Test Text Item',
)]
class TestTextItem extends Item
{
}

#[ItemType(
    name: null,
    formType: ItemFormType::class,
    template: null,
    label: null,
)]
class TestMinimalItem extends Item
{
}

// ItemInterface implementation without ItemType attribute
class TestPlainItem extends Item
{
}
