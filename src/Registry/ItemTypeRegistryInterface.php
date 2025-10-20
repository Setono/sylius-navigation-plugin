<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Registry;

use Setono\SyliusNavigationPlugin\Factory\ItemFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;

interface ItemTypeRegistryInterface
{
    /**
     * @param class-string $entity
     * @param class-string<FormTypeInterface<mixed>> $form
     */
    public function register(string $name, string $label, string $entity, string $form, string $template, ItemFactoryInterface $factory): void;

    /**
     * @throws \InvalidArgumentException if the item type is not registered
     */
    public function get(string $name): ItemType;

    /**
     * Check if an item type is registered
     */
    public function has(string $name): bool;

    /**
     * Get all registered item types indexed by name
     *
     * @return array<string, ItemType>
     */
    public function all(): array;
}
