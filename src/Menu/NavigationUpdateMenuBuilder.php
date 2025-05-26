<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Setono\SyliusNavigationPlugin\Event\NavigationMenuBuilderEvent;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NavigationUpdateMenuBuilder
{
    public const EVENT_NAME = 'setono_sylius_navigation.menu.admin.navigation.update';

    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function createMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        if (!isset($options['navigation'])) {
            return $menu;
        }

        $navigation = $options['navigation'];
        if (!$navigation instanceof NavigationInterface) {
            return $menu;
        }

        $menu->addChild(
            $this->factory
            ->createItem('build_from_taxon', [
                'route' => 'setono_sylius_navigation_admin_navigation_build_from_taxon',
                'routeParameters' => ['id' => $navigation->getId()],
            ])
            ->setAttribute('type', 'link')
            ->setLabel('setono_sylius_navigation.ui.build_from_taxon')
            ->setLabelAttribute('icon', 'cubes'),
        );

        $this->eventDispatcher->dispatch(
            new NavigationMenuBuilderEvent($this->factory, $menu, $navigation),
            self::EVENT_NAME,
        );

        return $menu;
    }
}
