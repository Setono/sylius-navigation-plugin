<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Provider;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;

interface ItemLabelProviderInterface
{
    public function getLabel(ItemInterface $item, ?string $locale = null): ?string;
}
