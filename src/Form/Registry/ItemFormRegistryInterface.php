<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Registry;

use Setono\SyliusNavigationPlugin\Attribute\NavigationItem;

interface ItemFormRegistryInterface
{
    /**
     * Register a form type with its metadata
     */
    public function register(string $formClass, NavigationItem $metadata): void;
    
    /**
     * Register a form type with individual metadata parameters
     */
    public function registerWithParams(string $formClass, string $name, ?string $template = null, ?string $label = null, int $priority = 0): void;
    
    /**
     * Get the form class for a given item type name
     * 
     * @throws \InvalidArgumentException if the form type is not registered
     */
    public function getFormClass(string $name): string;
    
    /**
     * Check if a form type is registered
     */
    public function has(string $name): bool;
    
    /**
     * Get the metadata for a given item type name
     * 
     * @throws \InvalidArgumentException if the form type is not registered
     */
    public function getMetadata(string $name): NavigationItem;
    
    /**
     * Get all registered form types with their metadata
     * 
     * @return array<string, array{class: string, metadata: NavigationItem}>
     */
    public function all(): array;
    
    /**
     * Get form types suitable for dropdown display, sorted by priority
     * 
     * @return array<string, string> Map of name => label
     */
    public function getFormTypesForDropdown(): array;
}