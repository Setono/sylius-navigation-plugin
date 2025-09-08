<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\DependencyInjection\Compiler;

use Setono\SyliusNavigationPlugin\Attribute\ItemType;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class RegisterNavigationItemsPass implements CompilerPassInterface
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

        /** @var array<string, array{classes: array{model: class-string}}> $resources */
        $resources = $container->getParameter('sylius.resources');

        foreach ($resources as $resource) {
            ['model' => $entity] = $resource['classes'];

            // Only process classes that implement ItemInterface
            if (!is_a($entity, ItemInterface::class, true)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($entity);
            $attributes = $reflectionClass->getAttributes(ItemType::class);

            if (count($attributes) === 0) {
                continue; // Skip entities without ItemType attribute
            }

            /** @var ItemType $metadata */
            $metadata = $attributes[0]->newInstance();

            if (!$container->has($metadata->formType)) {
                throw new ServiceNotFoundException($metadata->formType);
            }

            // Register the form type with the registry
            $registryDefinition->addMethodCall('register', [
                self::resolveName($entity, $metadata),
                self::resolveLabel($entity, $metadata),
                $entity,
                $metadata->formType,
                $metadata->template ?? '@SetonoSyliusNavigationPlugin/navigation/build/form/_default.html.twig',
            ]);
        }
    }

    /**
     * @param class-string<ItemInterface> $entity
     */
    private static function resolveName(string $entity, ItemType $itemType): string
    {
        return $itemType->name ?? Item::getType($entity);
    }

    /**
     * @param class-string<ItemInterface> $entity
     */
    private static function resolveLabel(string $entity, ItemType $itemType): string
    {
        return $itemType->label ?? sprintf('setono_sylius_navigation.item_types.%s', self::resolveName($entity, $itemType));
    }
}
