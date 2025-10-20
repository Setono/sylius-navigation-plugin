<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Registry;

final class ItemTypeRegistry implements ItemTypeRegistryInterface
{
    /** @var array<string, ItemType> */
    private array $types = [];

    public function register(string $name, string $label, string $entity, string $form, string $template): void
    {
        if (isset($this->types[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'An item type with name "%s" is already registered (class: %s)',
                $name,
                $this->types[$name]->entity,
            ));
        }

        $this->types[$name] = new ItemType($name, $label, $entity, $form, $template);
    }

    public function get(string $name): ItemType
    {
        if (!isset($this->types[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'No item type registered with name "%s". Available types: %s',
                $name,
                implode(', ', array_keys($this->types)),
            ));
        }

        return $this->types[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->types[$name]);
    }

    public function all(): array
    {
        return $this->types;
    }
}
