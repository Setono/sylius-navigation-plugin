<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Form\Type;

use Setono\SyliusNavigationPlugin\Attribute\NavigationItem;

#[NavigationItem(name: 'text', label: 'Text Item', priority: 10)]
final class BuilderTextItemType extends AbstractBuilderItemType
{
    // No additional fields needed for text items
    // All common fields are handled by the parent class
}