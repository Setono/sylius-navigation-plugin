<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Setono\SyliusNavigationPlugin\Renderer\CachedNavigationRenderer;
use Setono\SyliusNavigationPlugin\Renderer\NavigationRendererInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\Channel;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CachedNavigationRendererTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<NavigationRendererInterface> */
    private ObjectProphecy $decoratedRenderer;

    /** @var ObjectProphecy<CacheItemPoolInterface> */
    private ObjectProphecy $cachePool;

    /** @var ObjectProphecy<ChannelContextInterface> */
    private ObjectProphecy $channelContext;

    /** @var ObjectProphecy<LocaleContextInterface> */
    private ObjectProphecy $localeContext;

    /** @var ObjectProphecy<CacheItemInterface> */
    private ObjectProphecy $cacheItem;

    private CachedNavigationRenderer $renderer;

    private Channel $channel;

    protected function setUp(): void
    {
        $this->decoratedRenderer = $this->prophesize(NavigationRendererInterface::class);
        $this->cachePool = $this->prophesize(CacheItemPoolInterface::class);
        $this->channelContext = $this->prophesize(ChannelContextInterface::class);
        $this->localeContext = $this->prophesize(LocaleContextInterface::class);
        $this->cacheItem = $this->prophesize(CacheItemInterface::class);

        $this->channel = new Channel();
        $this->channel->setCode('WEB');

        $this->channelContext->getChannel()->willReturn($this->channel);
        $this->localeContext->getLocaleCode()->willReturn('en_US');

        $this->renderer = new CachedNavigationRenderer(
            $this->decoratedRenderer->reveal(),
            $this->cachePool->reveal(),
            $this->channelContext->reveal(),
            $this->localeContext->reveal(),
        );
    }

    /**
     * @test
     */
    public function it_returns_cached_result_when_available(): void
    {
        $navigation = new Navigation();
        $navigation->setCode('top');

        $channel = new Channel();
        $channel->setCode('WEB');

        $this->cacheItem->isHit()->willReturn(true);
        $this->cacheItem->get()->willReturn('<nav>cached</nav>');

        $this->cachePool
            ->getItem('setono_navigation_top_WEB_en_US')
            ->willReturn($this->cacheItem->reveal())
        ;

        $this->decoratedRenderer->render(Argument::cetera())->shouldNotBeCalled();

        $result = $this->renderer->render($navigation, null, $channel, 'en_US');

        self::assertSame('<nav>cached</nav>', $result);
    }

    /**
     * @test
     */
    public function it_caches_result_when_not_in_cache(): void
    {
        $navigation = new Navigation();
        $navigation->setCode('top');

        $channel = new Channel();
        $channel->setCode('WEB');

        $this->cacheItem->isHit()->willReturn(false);
        $this->cacheItem->set('<nav>rendered</nav>')->willReturn($this->cacheItem->reveal());

        $this->cachePool
            ->getItem('setono_navigation_top_WEB_en_US')
            ->willReturn($this->cacheItem->reveal())
        ;

        $this->cachePool
            ->save($this->cacheItem->reveal())
            ->shouldBeCalled()
        ;

        $this->decoratedRenderer
            ->render($navigation, null, $channel, 'en_US')
            ->willReturn('<nav>rendered</nav>')
            ->shouldBeCalled()
        ;

        $result = $this->renderer->render($navigation, null, $channel, 'en_US');

        self::assertSame('<nav>rendered</nav>', $result);
    }

    /**
     * @test
     */
    public function it_uses_channel_context_when_channel_is_null(): void
    {
        $navigation = new Navigation();
        $navigation->setCode('top');

        $this->cacheItem->isHit()->willReturn(false);
        $this->cacheItem->set('<nav>rendered</nav>')->willReturn($this->cacheItem->reveal());

        $this->cachePool
            ->getItem('setono_navigation_top_WEB_en_US')
            ->willReturn($this->cacheItem->reveal())
        ;

        $this->cachePool
            ->save($this->cacheItem->reveal())
            ->shouldBeCalled()
        ;

        // Channel context should be used when null is passed
        $this->decoratedRenderer
            ->render($navigation, null, $this->channel, 'en_US')
            ->willReturn('<nav>rendered</nav>')
            ->shouldBeCalled()
        ;

        $result = $this->renderer->render($navigation, null, null, 'en_US');

        self::assertSame('<nav>rendered</nav>', $result);
    }

    /**
     * @test
     */
    public function it_uses_locale_context_when_locale_is_null(): void
    {
        $navigation = new Navigation();
        $navigation->setCode('top');

        $channel = new Channel();
        $channel->setCode('WEB');

        $this->cacheItem->isHit()->willReturn(false);
        $this->cacheItem->set('<nav>rendered</nav>')->willReturn($this->cacheItem->reveal());

        $this->cachePool
            ->getItem('setono_navigation_top_WEB_en_US')
            ->willReturn($this->cacheItem->reveal())
        ;

        $this->cachePool
            ->save($this->cacheItem->reveal())
            ->shouldBeCalled()
        ;

        // Locale context should be used when null is passed
        $this->decoratedRenderer
            ->render($navigation, null, $channel, 'en_US')
            ->willReturn('<nav>rendered</nav>')
            ->shouldBeCalled()
        ;

        $result = $this->renderer->render($navigation, null, $channel, null);

        self::assertSame('<nav>rendered</nav>', $result);
    }

    /**
     * @test
     */
    public function it_invalidates_cache_for_navigation_without_tag_support(): void
    {
        $this->cachePool
            ->clear()
            ->shouldBeCalled()
        ;

        $this->renderer->invalidate('top');
    }

    /**
     * @test
     */
    public function it_invalidates_cache_for_navigation_with_tag_support(): void
    {
        $tagAwareCache = $this->prophesize(CacheItemPoolInterface::class);
        $tagAwareCache->willImplement(TagAwareCacheInterface::class);

        $tagAwareCache->invalidateTags(['setono_navigation_top'])->shouldBeCalled();

        $this->channelContext->getChannel()->willReturn($this->channel);
        $this->localeContext->getLocaleCode()->willReturn('en_US');

        $renderer = new CachedNavigationRenderer(
            $this->decoratedRenderer->reveal(),
            $tagAwareCache->reveal(),
            $this->channelContext->reveal(),
            $this->localeContext->reveal(),
        );

        $renderer->invalidate('top');
    }

    /**
     * @test
     */
    public function it_invalidates_all_cache(): void
    {
        $this->cachePool
            ->clear()
            ->shouldBeCalled()
        ;

        $this->renderer->invalidateAll();
    }

    /**
     * @test
     */
    public function it_renders_from_string_code(): void
    {
        $channel = new Channel();
        $channel->setCode('WEB');

        $this->cacheItem->isHit()->willReturn(false);
        $this->cacheItem->set('<nav>rendered</nav>')->willReturn($this->cacheItem->reveal());

        $this->cachePool
            ->getItem('setono_navigation_top_WEB_en_US')
            ->willReturn($this->cacheItem->reveal())
        ;

        $this->cachePool
            ->save($this->cacheItem->reveal())
            ->shouldBeCalled()
        ;

        $this->decoratedRenderer
            ->render('top', null, $channel, 'en_US')
            ->willReturn('<nav>rendered</nav>')
            ->shouldBeCalled()
        ;

        $result = $this->renderer->render('top', null, $channel, 'en_US');

        self::assertSame('<nav>rendered</nav>', $result);
    }
}
