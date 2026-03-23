<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Sylius\Component\Channel\Model\ChannelInterface;

final class ItemTest extends TestCase
{
    use ProphecyTrait;

    private function createItem(): Item
    {
        $item = new Item();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');

        return $item;
    }

    /**
     * @test
     */
    public function it_returns_null_id_by_default(): void
    {
        $item = $this->createItem();

        self::assertNull($item->getId());
    }

    /**
     * @test
     */
    public function it_has_no_label_by_default(): void
    {
        $item = $this->createItem();

        self::assertNull($item->getLabel());
    }

    /**
     * @test
     */
    public function it_allows_setting_label(): void
    {
        $item = $this->createItem();
        $item->setLabel('Home');

        self::assertSame('Home', $item->getLabel());
    }

    /**
     * @test
     */
    public function it_allows_setting_label_to_null(): void
    {
        $item = $this->createItem();
        $item->setLabel('Home');
        $item->setLabel(null);

        self::assertNull($item->getLabel());
    }

    /**
     * @test
     */
    public function it_converts_to_string_using_label(): void
    {
        $item = $this->createItem();
        $item->setLabel('About Us');

        self::assertSame('About Us', (string) $item);
    }

    /**
     * @test
     */
    public function it_converts_to_empty_string_when_label_is_null(): void
    {
        $item = $this->createItem();

        self::assertSame('', (string) $item);
    }

    /**
     * @test
     */
    public function it_has_no_navigation_by_default(): void
    {
        $item = $this->createItem();

        self::assertNull($item->getNavigation());
    }

    /**
     * @test
     */
    public function it_allows_setting_navigation(): void
    {
        $navigation = new Navigation();

        $item = $this->createItem();
        $item->setNavigation($navigation);

        self::assertSame($navigation, $item->getNavigation());
    }

    /**
     * @test
     */
    public function it_allows_setting_navigation_to_null(): void
    {
        $navigation = new Navigation();

        $item = $this->createItem();
        $item->setNavigation($navigation);
        $item->setNavigation(null);

        self::assertNull($item->getNavigation());
    }

    /**
     * @test
     */
    public function it_has_zero_position_by_default(): void
    {
        $item = $this->createItem();

        self::assertSame(0, $item->getPosition());
    }

    /**
     * @test
     */
    public function it_allows_setting_position(): void
    {
        $item = $this->createItem();
        $item->setPosition(5);

        self::assertSame(5, $item->getPosition());
    }

    /**
     * @test
     */
    public function it_has_empty_channels_by_default(): void
    {
        $item = $this->createItem();

        self::assertTrue($item->getChannels()->isEmpty());
    }

    /**
     * @test
     */
    public function it_allows_adding_a_channel(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $item = $this->createItem();
        $item->addChannel($channel->reveal());

        self::assertTrue($item->hasChannel($channel->reveal()));
        self::assertCount(1, $item->getChannels());
    }

    /**
     * @test
     */
    public function it_does_not_add_duplicate_channel(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $item = $this->createItem();
        $item->addChannel($channel->reveal());
        $item->addChannel($channel->reveal());

        self::assertCount(1, $item->getChannels());
    }

    /**
     * @test
     */
    public function it_allows_removing_a_channel(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $item = $this->createItem();
        $item->addChannel($channel->reveal());
        $item->removeChannel($channel->reveal());

        self::assertFalse($item->hasChannel($channel->reveal()));
        self::assertTrue($item->getChannels()->isEmpty());
    }

    /**
     * @test
     */
    public function it_does_nothing_when_removing_a_channel_that_was_not_added(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $item = $this->createItem();
        $item->removeChannel($channel->reveal());

        self::assertTrue($item->getChannels()->isEmpty());
    }

    /**
     * @test
     */
    public function it_is_enabled_by_default(): void
    {
        $item = $this->createItem();

        self::assertTrue($item->isEnabled());
    }

    /**
     * @test
     */
    public function it_can_be_disabled(): void
    {
        $item = $this->createItem();
        $item->setEnabled(false);

        self::assertFalse($item->isEnabled());
    }
}
