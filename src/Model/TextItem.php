<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Setono\SyliusNavigationPlugin\Attribute\ItemType;
use Setono\SyliusNavigationPlugin\Form\Type\TextItemType;

#[ItemType(name: 'text', formType: TextItemType::class, label: 'Text Item')]
class TextItem extends Item implements TextItemInterface
{
}
