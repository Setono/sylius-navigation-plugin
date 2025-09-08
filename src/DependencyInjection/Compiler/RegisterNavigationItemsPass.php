<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\DependencyInjection\Compiler;

use Setono\SyliusNavigationPlugin\Attribute\NavigationItem;
use Setono\SyliusNavigationPlugin\Form\Registry\ItemFormRegistryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterNavigationItemsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $registryServiceId = null;
        if ($container->hasDefinition(ItemFormRegistryInterface::class)) {
            $registryServiceId = ItemFormRegistryInterface::class;
        } elseif ($container->hasAlias(ItemFormRegistryInterface::class)) {
            $alias = $container->getAlias(ItemFormRegistryInterface::class);
            $registryServiceId = (string) $alias;
        } elseif ($container->hasDefinition('Setono\SyliusNavigationPlugin\Form\Registry\ItemFormRegistry')) {
            $registryServiceId = 'Setono\SyliusNavigationPlugin\Form\Registry\ItemFormRegistry';
        }
        
        if (!$registryServiceId) {
            return;
        }
        
        $registryDefinition = $container->getDefinition($registryServiceId);
        $taggedServices = $container->findTaggedServiceIds('setono_sylius_navigation.item');

        foreach ($taggedServices as $id => $tags) {
            $formDefinition = $container->getDefinition($id);
            $formClass = $formDefinition->getClass();
            
            if ($formClass === null) {
                continue;
            }
            
            // Handle parameter references in class names (e.g., %parameter%)
            if (str_starts_with($formClass, '%') && str_ends_with($formClass, '%')) {
                $parameterName = substr($formClass, 1, -1);
                if ($container->hasParameter($parameterName)) {
                    $formClass = $container->getParameter($parameterName);
                }
            }
            
            if (!class_exists($formClass)) {
                throw new \RuntimeException(sprintf(
                    'Form class "%s" for service "%s" does not exist',
                    $formClass,
                    $id
                ));
            }
            
            $reflectionClass = new \ReflectionClass($formClass);
            $attributes = $reflectionClass->getAttributes(NavigationItem::class);
            
            if (count($attributes) === 0) {
                throw new \RuntimeException(sprintf(
                    'Form class "%s" must have the NavigationItem attribute',
                    $formClass
                ));
            }
            
            /** @var NavigationItem $metadata */
            $metadata = $attributes[0]->newInstance();
            
            // Use registerWithParams to pass individual parameters instead of the object
            $registryDefinition->addMethodCall('registerWithParams', [
                $formClass,
                $metadata->name,
                $metadata->template,
                $metadata->label,
                $metadata->priority,
            ]);
            
        }
    }
}