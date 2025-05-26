<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class NavigationMenuBuilderEvent extends MenuBuilderEvent
{
    public function __construct(
        FactoryInterface $factory,
        ItemInterface $menu,
        public readonly NavigationInterface $navigation,
    ) {
        parent::__construct($factory, $menu);
    }
}
