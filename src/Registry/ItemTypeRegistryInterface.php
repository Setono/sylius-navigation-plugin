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
     * @throws \InvalidArgumentException if the item type is not registered
     */
    public function get(string $name): ItemType;

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
     * Get all registered item types
     *
     * @return array<string, ItemType>
     */
    public function all(): array;
}
