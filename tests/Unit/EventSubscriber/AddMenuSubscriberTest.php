<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\EventSubscriber;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusNavigationPlugin\EventSubscriber\AddMenuSubscriber;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AddMenuSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<MenuBuilderEvent> */
    private ObjectProphecy $event;

    /** @var ObjectProphecy<ItemInterface> */
    private ObjectProphecy $menu;

    protected function setUp(): void
    {
        $this->event = $this->prophesize(MenuBuilderEvent::class);
        $this->menu = $this->prophesize(ItemInterface::class);

        $this->event->getMenu()->willReturn($this->menu->reveal());
    }

    /**
     * @test
     */
    public function it_subscribes_to_the_admin_main_menu_event(): void
    {
        self::assertArrayHasKey('sylius.menu.admin.main', AddMenuSubscriber::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function it_adds_navigation_item_under_catalog_when_catalog_exists(): void
    {
        $catalog = $this->prophesize(ItemInterface::class);
        $navigationItem = $this->prophesize(ItemInterface::class);

        $this->menu->getChild('catalog')->willReturn($catalog->reveal());

        $catalog->addChild('navigation', [
            'route' => 'setono_sylius_navigation_admin_navigation_index',
        ])->willReturn($navigationItem->reveal())->shouldBeCalledOnce();

        $navigationItem->setLabel('setono_sylius_navigation.ui.navigation')
            ->willReturn($navigationItem->reveal())
            ->shouldBeCalledOnce();

        $navigationItem->setLabelAttribute('icon', 'align justify')
            ->willReturn($navigationItem->reveal())
            ->shouldBeCalledOnce();

        $subscriber = new AddMenuSubscriber();
        $subscriber->add($this->event->reveal());
    }

    /**
     * @test
     */
    public function it_adds_navigation_item_under_first_child_when_catalog_does_not_exist(): void
    {
        $firstChild = $this->prophesize(ItemInterface::class);
        $navigationItem = $this->prophesize(ItemInterface::class);

        $this->menu->getChild('catalog')->willReturn(null);
        $this->menu->getFirstChild()->willReturn($firstChild->reveal());

        $firstChild->addChild('navigation', [
            'route' => 'setono_sylius_navigation_admin_navigation_index',
        ])->willReturn($navigationItem->reveal())->shouldBeCalledOnce();

        $navigationItem->setLabel('setono_sylius_navigation.ui.navigation')
            ->willReturn($navigationItem->reveal())
            ->shouldBeCalledOnce();

        $navigationItem->setLabelAttribute('icon', 'align justify')
            ->willReturn($navigationItem->reveal())
            ->shouldBeCalledOnce();

        $subscriber = new AddMenuSubscriber();
        $subscriber->add($this->event->reveal());
    }
}
