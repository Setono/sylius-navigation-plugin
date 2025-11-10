<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\DependencyInjection\Compiler;

use Setono\SyliusNavigationPlugin\Attribute\ItemType;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;
use function Symfony\Component\String\u;

final class RegisterItemTypesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ItemTypeRegistryInterface::class)) {
            return;
        }

        if (!$container->hasParameter('sylius.resources')) {
            return;
        }

        $registryDefinition = $container->findDefinition(ItemTypeRegistryInterface::class);

        /** @var array<string, array{classes: array{model: class-string, factory: class-string}}> $resources */
        $resources = $container->getParameter('sylius.resources');

        foreach ($resources as $resourceName => $resource) {
            $entity = $resource['classes']['model'];

            // Only process classes that implement ItemInterface
            if (!is_a($entity, ItemInterface::class, true)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($entity);
            $attributes = self::getItemTypeAttributeFromHierarchy($reflectionClass);

            if ([] === $attributes) {
                continue; // Skip entities without ItemType attribute
            }

            /** @var ItemType $metadata */
            $metadata = $attributes[0]->newInstance();

            if (!$container->has($metadata->formType)) {
                throw new ServiceNotFoundException($metadata->formType);
            }

            $name = $metadata->name ?? u($reflectionClass->getShortName())->snake()->toString();

            // Construct the factory service ID from the resource name
            // e.g., 'setono_sylius_navigation.text_item' -> 'setono_sylius_navigation.factory.text_item'
            $factoryServiceId = str_replace('.', '.factory.', $resourceName);

            // Validate that the factory service exists
            if (!$container->has($factoryServiceId)) {
                throw new ServiceNotFoundException(sprintf(
                    'Factory service "%s" not found for item type "%s". Expected service to be registered by Sylius Resource Bundle.',
                    $factoryServiceId,
                    $name,
                ));
            }

            $registryDefinition->addMethodCall('register', [
                $name,
                $metadata->label ?? sprintf('setono_sylius_navigation.item_types.%s', $name),
                $entity,
                $metadata->formType,
                $metadata->template ?? '@SetonoSyliusNavigationPlugin/navigation/build/form/_default.html.twig',
                new Reference($factoryServiceId),
            ]);
        }
    }

    /**
     * Get ItemType attributes from the class hierarchy, starting from the current class
     * and walking up to parent classes until an attribute is found.
     *
     * @param \ReflectionClass<object> $reflectionClass
     *
     * @return list<\ReflectionAttribute<ItemType>>
     */
    private static function getItemTypeAttributeFromHierarchy(\ReflectionClass $reflectionClass): array
    {
        do {
            $attributes = $reflectionClass->getAttributes(ItemType::class);
            if (count($attributes) > 0) {
                return $attributes;
            }

            $reflectionClass = $reflectionClass->getParentClass();
        } while (false !== $reflectionClass);

        return [];
    }
}
