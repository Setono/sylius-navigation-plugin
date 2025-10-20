<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Registry;

use Symfony\Component\Form\FormTypeInterface;

interface ItemTypeRegistryInterface
{
    /**
     * @param class-string $entity
     * @param class-string<FormTypeInterface<mixed>> $form
     */
    public function register(string $name, string $label, string $entity, string $form, string $template): void;

    /**
     * @return array{name: string, label: string, entity: class-string, form: class-string, template: string}
     *
     * @throws \InvalidArgumentException if the item type is not registered
     */
    public function getType(string $name): array;

    /**
     * Get the form class for a given item type name
     *
     * @return class-string<\Symfony\Component\Form\FormTypeInterface<mixed>>
     *
     * @throws \InvalidArgumentException if the item type is not registered
     */
    public function getForm(string $name): string;

    /**
     * Check if an item type is registered
     */
    public function has(string $name): bool;

    /**
     * Get form types suitable for dropdown display, sorted by priority
     *
     * @return array<string, string> Map of name => label
     */
    public function getFormTypesForDropdown(): array;
}
