<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Attribute;

use Symfony\Component\Form\FormTypeInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ItemType
{
    public function __construct(
        /**
         * The unique identifier for this navigation item type (e.g., 'text', 'taxon')
         * If it's not set, we will generate it based on the entity you've annotated
         */
        public readonly ?string $name,

        /**
         * The form type class to use for this entity
         *
         * @var class-string<FormTypeInterface<mixed>> $formType
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
