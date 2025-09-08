<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Attribute;

/**
 * Attribute to mark and configure navigation item form types
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class NavigationItem
{
    public function __construct(
        /**
         * The unique identifier for this navigation item type (e.g., 'text', 'taxon')
         */
        public readonly string $name,
        
        /**
         * Optional custom template for rendering the form
         * If null, the default template will be used
         */
        public readonly ?string $template = null,
        
        /**
         * Human-readable label for the item type (used in dropdowns)
         * If null, will be generated from the name
         */
        public readonly ?string $label = null,
        
        /**
         * Priority for ordering in dropdowns (higher = first)
         */
        public readonly int $priority = 0,
    ) {
    }
    
    /**
     * Get the label, falling back to a formatted version of the name if not set
     */
    public function getLabel(): string
    {
        return $this->label ?? ucfirst(str_replace('_', ' ', $this->name));
    }
}