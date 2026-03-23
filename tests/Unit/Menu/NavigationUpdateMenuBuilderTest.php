<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Event\NavigationMenuBuilderEvent;
use Setono\SyliusNavigationPlugin\Menu\NavigationUpdateMenuBuilder;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class NavigationUpdateMenuBuilderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_returns_empty_menu_when_navigation_option_is_missing(): void
    {
        $rootMenu = $this->prophesize(ItemInterface::class);
        $factory = $this->prophesize(FactoryInterface::class);
        $factory->createItem('root')->willReturn($rootMenu->reveal());

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();

        $builder = new NavigationUpdateMenuBuilder($factory->reveal(), $eventDispatcher->reveal());
        $result = $builder->createMenu([]);

        self::assertSame($rootMenu->reveal(), $result);
    }

    /**
     * @test
     */
    public function it_returns_empty_menu_when_navigation_option_is_not_navigation_interface(): void
    {
        $rootMenu = $this->prophesize(ItemInterface::class);
        $factory = $this->prophesize(FactoryInterface::class);
        $factory->createItem('root')->willReturn($rootMenu->reveal());

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();

        $builder = new NavigationUpdateMenuBuilder($factory->reveal(), $eventDispatcher->reveal());
        $result = $builder->createMenu(['navigation' => 'not_a_navigation_object']);

        self::assertSame($rootMenu->reveal(), $result);
    }

    /**
     * @test
     */
    public function it_adds_build_from_taxon_child_and_dispatches_event(): void
    {
        $navigation = $this->prophesize(NavigationInterface::class);
        $navigation->getId()->willReturn(42);

        $childItem = $this->prophesize(ItemInterface::class);
        $childItem->setAttribute('type', 'link')->willReturn($childItem->reveal());
        $childItem->setLabel('setono_sylius_navigation.ui.build_from_taxon')->willReturn($childItem->reveal());
        $childItem->setLabelAttribute('icon', 'cubes')->willReturn($childItem->reveal());

        $rootMenu = $this->prophesize(ItemInterface::class);
        $rootMenu->addChild($childItem->reveal())->shouldBeCalledOnce();

        $factory = $this->prophesize(FactoryInterface::class);
        $factory->createItem('root')->willReturn($rootMenu->reveal());
        $factory->createItem('build_from_taxon', [
            'route' => 'setono_sylius_navigation_admin_navigation_build_from_taxon',
            'routeParameters' => ['navigation' => 42],
        ])->willReturn($childItem->reveal());

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(
            Argument::type(NavigationMenuBuilderEvent::class),
            NavigationUpdateMenuBuilder::EVENT_NAME,
        )->shouldBeCalledOnce();

        $builder = new NavigationUpdateMenuBuilder($factory->reveal(), $eventDispatcher->reveal());
        $result = $builder->createMenu(['navigation' => $navigation->reveal()]);

        self::assertSame($rootMenu->reveal(), $result);
    }

    /**
     * @test
     */
    public function it_has_correct_event_name_constant(): void
    {
        self::assertSame(
            'setono_sylius_navigation.menu.admin.navigation.update',
            NavigationUpdateMenuBuilder::EVENT_NAME,
        );
    }
}
