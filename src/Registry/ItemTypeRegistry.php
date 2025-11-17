<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Registry;

use Setono\SyliusNavigationPlugin\Factory\ItemFactoryInterface;

final class ItemTypeRegistry implements ItemTypeRegistryInterface
{
    /** @var array<string, ItemType> */
    private array $types = [];

    public function register(string $name, string $label, string $entity, string $form, string $template, ItemFactoryInterface $factory, array $options = []): void
    {
        if (isset($this->types[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'An item type with name "%s" is already registered (class: %s)',
                $name,
                $this->types[$name]->entity,
            ));
        }

        $this->types[$name] = new ItemType($name, $label, $entity, $form, $template, $factory, $options);
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

    public function getByEntity(string $entity): ItemType
    {
        foreach ($this->types as $type) {
            if ($type->entity === $entity) {
                return $type;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'No item type registered for entity class "%s". Available entities: %s',
            $entity,
            implode(', ', array_map(static fn (ItemType $type): string => sprintf('%s (%s)', $type->entity, $type->name), $this->types)),
        ));
    }
}
