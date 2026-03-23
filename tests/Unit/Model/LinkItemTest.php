<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusNavigationPlugin\Model\LinkItem;

final class LinkItemTest extends TestCase
{
    private function createLinkItem(): LinkItem
    {
        $item = new LinkItem();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');

        return $item;
    }

    /**
     * @test
     */
    public function it_has_no_url_by_default(): void
    {
        $item = $this->createLinkItem();

        self::assertNull($item->getUrl());
    }

    /**
     * @test
     */
    public function it_allows_setting_url(): void
    {
        $item = $this->createLinkItem();
        $item->setUrl('https://example.com');

        self::assertSame('https://example.com', $item->getUrl());
    }

    /**
     * @test
     */
    public function it_allows_setting_url_to_null(): void
    {
        $item = $this->createLinkItem();
        $item->setUrl('https://example.com');
        $item->setUrl(null);

        self::assertNull($item->getUrl());
    }

    /**
     * @test
     */
    public function it_does_not_open_in_new_tab_by_default(): void
    {
        $item = $this->createLinkItem();

        self::assertFalse($item->isOpenInNewTab());
    }

    /**
     * @test
     */
    public function it_allows_setting_open_in_new_tab(): void
    {
        $item = $this->createLinkItem();
        $item->setOpenInNewTab(true);

        self::assertTrue($item->isOpenInNewTab());
    }

    /**
     * @test
     */
    public function it_is_not_nofollow_by_default(): void
    {
        $item = $this->createLinkItem();

        self::assertFalse($item->isNofollow());
    }

    /**
     * @test
     */
    public function it_allows_setting_nofollow(): void
    {
        $item = $this->createLinkItem();
        $item->setNofollow(true);

        self::assertTrue($item->isNofollow());
    }

    /**
     * @test
     */
    public function it_is_not_noopener_by_default(): void
    {
        $item = $this->createLinkItem();

        self::assertFalse($item->isNoopener());
    }

    /**
     * @test
     */
    public function it_allows_setting_noopener(): void
    {
        $item = $this->createLinkItem();
        $item->setNoopener(true);

        self::assertTrue($item->isNoopener());
    }

    /**
     * @test
     */
    public function it_is_not_noreferrer_by_default(): void
    {
        $item = $this->createLinkItem();

        self::assertFalse($item->isNoreferrer());
    }

    /**
     * @test
     */
    public function it_allows_setting_noreferrer(): void
    {
        $item = $this->createLinkItem();
        $item->setNoreferrer(true);

        self::assertTrue($item->isNoreferrer());
    }

    /**
     * @test
     */
    public function it_returns_null_target_when_not_open_in_new_tab(): void
    {
        $item = $this->createLinkItem();

        self::assertNull($item->getTarget());
    }

    /**
     * @test
     */
    public function it_returns_blank_target_when_open_in_new_tab(): void
    {
        $item = $this->createLinkItem();
        $item->setOpenInNewTab(true);

        self::assertSame('_blank', $item->getTarget());
    }

    /**
     * @test
     */
    public function it_returns_null_rel_when_no_flags_are_set(): void
    {
        $item = $this->createLinkItem();

        self::assertNull($item->getRel());
    }

    /**
     * @test
     */
    public function it_returns_nofollow_rel_when_nofollow_is_set(): void
    {
        $item = $this->createLinkItem();
        $item->setNofollow(true);

        self::assertSame('nofollow', $item->getRel());
    }

    /**
     * @test
     */
    public function it_returns_noopener_rel_when_noopener_is_set(): void
    {
        $item = $this->createLinkItem();
        $item->setNoopener(true);

        self::assertSame('noopener', $item->getRel());
    }

    /**
     * @test
     */
    public function it_returns_noreferrer_rel_when_noreferrer_is_set(): void
    {
        $item = $this->createLinkItem();
        $item->setNoreferrer(true);

        self::assertSame('noreferrer', $item->getRel());
    }

    /**
     * @test
     */
    public function it_returns_combined_rel_when_multiple_flags_are_set(): void
    {
        $item = $this->createLinkItem();
        $item->setNofollow(true);
        $item->setNoopener(true);
        $item->setNoreferrer(true);

        self::assertSame('nofollow noopener noreferrer', $item->getRel());
    }

    /**
     * @test
     */
    public function it_returns_partial_rel_when_some_flags_are_set(): void
    {
        $item = $this->createLinkItem();
        $item->setNofollow(true);
        $item->setNoreferrer(true);

        self::assertSame('nofollow noreferrer', $item->getRel());
    }

    /**
     * @test
     */
    public function it_returns_null_rel_after_unsetting_all_flags(): void
    {
        $item = $this->createLinkItem();
        $item->setNofollow(true);
        $item->setNoopener(true);
        $item->setNofollow(false);
        $item->setNoopener(false);

        self::assertNull($item->getRel());
    }
}
