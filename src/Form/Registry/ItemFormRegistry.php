<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Registry;

use Setono\SyliusNavigationPlugin\Attribute\NavigationItem;

final class ItemFormRegistry implements ItemFormRegistryInterface
{
    /**
     * @var array<string, array{class: string, metadata: NavigationItem}>
     */
    private array $forms = [];
    
    public function register(string $formClass, NavigationItem $metadata): void
    {
        if (isset($this->forms[$metadata->name])) {
            throw new \InvalidArgumentException(sprintf(
                'A form type with name "%s" is already registered (class: %s)',
                $metadata->name,
                $this->forms[$metadata->name]['class']
            ));
        }
        
        $this->forms[$metadata->name] = [
            'class' => $formClass,
            'metadata' => $metadata,
        ];
    }
    
    public function registerWithParams(string $formClass, string $name, ?string $template = null, ?string $label = null, int $priority = 0): void
    {
        $metadata = new NavigationItem($name, $template, $label, $priority);
        $this->register($formClass, $metadata);
    }
    
    public function getFormClass(string $name): string
    {
        if (!isset($this->forms[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'No form type registered with name "%s". Available types: %s',
                $name,
                implode(', ', array_keys($this->forms))
            ));
        }
        
        return $this->forms[$name]['class'];
    }
    
    public function has(string $name): bool
    {
        return isset($this->forms[$name]);
    }
    
    public function getMetadata(string $name): NavigationItem
    {
        if (!isset($this->forms[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'No form type registered with name "%s"',
                $name
            ));
        }
        
        return $this->forms[$name]['metadata'];
    }
    
    public function all(): array
    {
        return $this->forms;
    }
    
    public function getFormTypesForDropdown(): array
    {
        // Sort by priority (descending) then by name
        $sorted = $this->forms;
        uasort($sorted, function (array $a, array $b): int {
            $priorityComparison = $b['metadata']->priority <=> $a['metadata']->priority;
            if ($priorityComparison !== 0) {
                return $priorityComparison;
            }
            
            return $a['metadata']->name <=> $b['metadata']->name;
        });
        
        $dropdown = [];
        foreach ($sorted as $name => $data) {
            $dropdown[$name] = $data['metadata']->getLabel();
        }
        
        return $dropdown;
    }
}