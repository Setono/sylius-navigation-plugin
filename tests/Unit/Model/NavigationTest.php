<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

final class NavigationTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_returns_null_id_by_default(): void
    {
        $navigation = new Navigation();

        self::assertNull($navigation->getId());
    }

    /**
     * @test
     */
    public function it_has_no_code_by_default(): void
    {
        $navigation = new Navigation();

        self::assertNull($navigation->getCode());
    }

    /**
     * @test
     */
    public function it_allows_setting_code(): void
    {
        $navigation = new Navigation();
        $navigation->setCode('main_menu');

        self::assertSame('main_menu', $navigation->getCode());
    }

    /**
     * @test
     */
    public function it_allows_setting_code_to_null(): void
    {
        $navigation = new Navigation();
        $navigation->setCode('main_menu');
        $navigation->setCode(null);

        self::assertNull($navigation->getCode());
    }

    /**
     * @test
     */
    public function it_has_no_description_by_default(): void
    {
        $navigation = new Navigation();

        self::assertNull($navigation->getDescription());
    }

    /**
     * @test
     */
    public function it_allows_setting_description(): void
    {
        $navigation = new Navigation();
        $navigation->setDescription('Main navigation menu');

        self::assertSame('Main navigation menu', $navigation->getDescription());
    }

    /**
     * @test
     */
    public function it_allows_setting_description_to_null(): void
    {
        $navigation = new Navigation();
        $navigation->setDescription('Some description');
        $navigation->setDescription(null);

        self::assertNull($navigation->getDescription());
    }

    /**
     * @test
     */
    public function it_has_pending_state_by_default(): void
    {
        $navigation = new Navigation();

        self::assertSame(NavigationInterface::STATE_PENDING, $navigation->getState());
    }

    /**
     * @test
     */
    public function it_allows_setting_state(): void
    {
        $navigation = new Navigation();
        $navigation->setState(NavigationInterface::STATE_BUILDING);

        self::assertSame(NavigationInterface::STATE_BUILDING, $navigation->getState());
    }

    /**
     * @test
     */
    public function it_has_empty_channels_by_default(): void
    {
        $navigation = new Navigation();

        self::assertTrue($navigation->getChannels()->isEmpty());
    }

    /**
     * @test
     */
    public function it_allows_adding_a_channel(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $navigation = new Navigation();
        $navigation->addChannel($channel->reveal());

        self::assertTrue($navigation->hasChannel($channel->reveal()));
        self::assertCount(1, $navigation->getChannels());
    }

    /**
     * @test
     */
    public function it_does_not_add_duplicate_channel(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $navigation = new Navigation();
        $navigation->addChannel($channel->reveal());
        $navigation->addChannel($channel->reveal());

        self::assertCount(1, $navigation->getChannels());
    }

    /**
     * @test
     */
    public function it_allows_removing_a_channel(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $navigation = new Navigation();
        $navigation->addChannel($channel->reveal());
        $navigation->removeChannel($channel->reveal());

        self::assertFalse($navigation->hasChannel($channel->reveal()));
        self::assertTrue($navigation->getChannels()->isEmpty());
    }

    /**
     * @test
     */
    public function it_does_nothing_when_removing_a_channel_that_was_not_added(): void
    {
        $channel = $this->prophesize(ChannelInterface::class);

        $navigation = new Navigation();
        $navigation->removeChannel($channel->reveal());

        self::assertTrue($navigation->getChannels()->isEmpty());
    }

    /**
     * @test
     */
    public function it_has_empty_items_by_default(): void
    {
        $navigation = new Navigation();

        self::assertTrue($navigation->getItems()->isEmpty());
    }

    /**
     * @test
     */
    public function it_is_enabled_by_default(): void
    {
        $navigation = new Navigation();

        self::assertTrue($navigation->isEnabled());
    }

    /**
     * @test
     */
    public function it_can_be_disabled(): void
    {
        $navigation = new Navigation();
        $navigation->setEnabled(false);

        self::assertFalse($navigation->isEnabled());
    }
}
