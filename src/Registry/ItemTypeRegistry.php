<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Registry;

use Symfony\Component\Form\FormTypeInterface;

final class ItemTypeRegistry implements ItemTypeRegistryInterface
{
    /**
     * An array of defined types. The key is the name, and the value is an array of metadata
     *
     * @var array<string, array{name: string, label: string, entity: class-string, form: class-string<FormTypeInterface<mixed>>, template: string}>
     */
    private array $types = [];

    public function register(string $name, string $label, string $entity, string $form, string $template): void
    {
        if (isset($this->types[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'An item type with name "%s" is already registered (class: %s)',
                $name,
                $this->types[$name]['entity'],
            ));
        }

        $this->types[$name] = [
            'name' => $name,
            'label' => $label,
            'entity' => $entity,
            'form' => $form,
            'template' => $template,
        ];
    }

    public function get(string $name): array
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

    public function getForm(string $name): string
    {
        if (!isset($this->types[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'No item type registered with name "%s". Available types: %s',
                $name,
                implode(', ', array_keys($this->types)),
            ));
        }

        return $this->types[$name]['form'];
    }

    public function has(string $name): bool
    {
        return isset($this->types[$name]);
    }

    public function getFormTypesForDropdown(): array
    {
        return array_combine(array_keys($this->types), array_map(static fn (array $type): string => $type['label'], $this->types));
    }
}
