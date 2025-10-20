<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Registry;

use Symfony\Component\Form\FormTypeInterface;

/**
 * @psalm-immutable
 */
final class ItemType
{
    /**
     * @param class-string $entity
     * @param class-string<FormTypeInterface<mixed>> $form
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $entity,
        public readonly string $form,
        public readonly string $template,
    ) {
    }
}
