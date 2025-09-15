<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Attribute;

use Symfony\Component\Form\FormTypeInterface;

/**
 * Attribute to mark and configure navigation item entities
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ItemType
{
    public function __construct(
        /**
         * The unique identifier for this navigation item type (e.g., 'text', 'taxon')
         * If null, will be derived from the entity class name using \Setono\SyliusNavigationPlugin\Model\Item::getType
         */
        public readonly ?string $name,

        /**
         * The form type class to use for this entity
         *
         * @var class-string<FormTypeInterface> $formType
         */
        public readonly string $formType,

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
    ) {
    }
}
