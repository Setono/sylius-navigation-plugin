<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Twig;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistryInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    public function __construct(
        private readonly ItemTypeRegistryInterface $itemTypeRegistry,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            /** @phpstan-ignore argument.type */
            new TwigFunction('ssn_navigation', [Runtime::class, 'navigation'], ['is_safe' => ['html']]),
            /** @phpstan-ignore argument.type */
            new TwigFunction('ssn_item', [Runtime::class, 'item'], ['is_safe' => ['html']]),
            new TwigFunction('ssn_item_type', $this->getItemType(...)),
            new TwigFunction('ssn_item_type_icon', $this->getItemTypeIcon(...)),
        ];
    }

    public function getItemType(ItemInterface $item): string
    {
        return $this->itemTypeRegistry->getByEntity($item::class)->name;
    }

    public function getItemTypeIcon(ItemInterface $item): string
    {
        $itemType = $this->itemTypeRegistry->getByEntity($item::class);
        $icon = $itemType->options['icon'] ?? 'file text icon';

        assert(is_string($icon));

        return $icon;
    }
}
