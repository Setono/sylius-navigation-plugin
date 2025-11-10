<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Cache\CacheItemPoolInterface;
use Setono\SyliusNavigationPlugin\EventListener\Doctrine\ItemBasedNavigationCacheInvalidatorListener;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Renderer\CachedNavigationRenderer;
use Setono\SyliusNavigationPlugin\Renderer\NavigationRendererInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class ItemBasedNavigationCacheInvalidatorListenerTest extends TestCase
{
    use ProphecyTrait;

    private CachedNavigationRenderer $cachedRenderer;

    private ObjectProphecy $cachePool;

    private ItemBasedNavigationCacheInvalidatorListener $listener;

    protected function setUp(): void
    {
        $decoratedRenderer = $this->prophesize(NavigationRendererInterface::class);
        $this->cachePool = $this->prophesize(CacheItemPoolInterface::class);
        $this->cachePool->willImplement(TagAwareCacheInterface::class);
        $channelContext = $this->prophesize(ChannelContextInterface::class);
        $localeContext = $this->prophesize(LocaleContextInterface::class);

        $this->cachedRenderer = new CachedNavigationRenderer(
            $decoratedRenderer->reveal(),
            $this->cachePool->reveal(),
            $channelContext->reveal(),
            $localeContext->reveal(),
        );

        $this->listener = new ItemBasedNavigationCacheInvalidatorListener($this->cachedRenderer);
    }

    /**
     * @test
     */
    public function it_invalidates_cache_on_post_update_when_navigation_is_updated(): void
    {
        $navigation = $this->createNavigation('main-menu');
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostUpdateEventArgs($navigation, $entityManager);

        $this->cachePool->invalidateTags(['setono_navigation_main-menu'])->shouldBeCalledOnce();

        $this->listener->postUpdate($eventArgs);
    }

    /**
     * @test
     */
    public function it_invalidates_cache_on_post_remove_when_navigation_is_removed(): void
    {
        $navigation = $this->createNavigation('main-menu');
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostRemoveEventArgs($navigation, $entityManager);

        $this->cachePool->invalidateTags(['setono_navigation_main-menu'])->shouldBeCalledOnce();

        $this->listener->postRemove($eventArgs);
    }

    /**
     * @test
     */
    public function it_invalidates_cache_on_post_persist_when_navigation_is_created(): void
    {
        $navigation = $this->createNavigation('main-menu');
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostPersistEventArgs($navigation, $entityManager);

        $this->cachePool->invalidateTags(['setono_navigation_main-menu'])->shouldBeCalledOnce();

        $this->listener->postPersist($eventArgs);
    }

    /**
     * @test
     */
    public function it_invalidates_cache_on_post_update_when_item_is_updated(): void
    {
        $navigation = $this->createNavigation('main-menu');
        $item = $this->createItem($navigation);
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostUpdateEventArgs($item, $entityManager);

        $this->cachePool->invalidateTags(['setono_navigation_main-menu'])->shouldBeCalledOnce();

        $this->listener->postUpdate($eventArgs);
    }

    /**
     * @test
     */
    public function it_invalidates_cache_on_post_remove_when_item_is_removed(): void
    {
        $navigation = $this->createNavigation('main-menu');
        $item = $this->createItem($navigation);
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostRemoveEventArgs($item, $entityManager);

        $this->cachePool->invalidateTags(['setono_navigation_main-menu'])->shouldBeCalledOnce();

        $this->listener->postRemove($eventArgs);
    }

    /**
     * @test
     */
    public function it_invalidates_cache_on_post_persist_when_item_is_created(): void
    {
        $navigation = $this->createNavigation('main-menu');
        $item = $this->createItem($navigation);
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostPersistEventArgs($item, $entityManager);

        $this->cachePool->invalidateTags(['setono_navigation_main-menu'])->shouldBeCalledOnce();

        $this->listener->postPersist($eventArgs);
    }

    /**
     * @test
     */
    public function it_does_not_invalidate_cache_when_item_has_no_navigation(): void
    {
        $item = $this->createItem(null);
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostUpdateEventArgs($item, $entityManager);

        $this->cachePool->invalidateTags(Argument::any())->shouldNotBeCalled();
        $this->cachePool->clear()->shouldNotBeCalled();

        $this->listener->postUpdate($eventArgs);
    }

    /**
     * @test
     */
    public function it_does_not_invalidate_cache_for_unrelated_entities(): void
    {
        $unrelatedEntity = new \stdClass();
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostUpdateEventArgs($unrelatedEntity, $entityManager);

        $this->cachePool->invalidateTags(Argument::any())->shouldNotBeCalled();
        $this->cachePool->clear()->shouldNotBeCalled();

        $this->listener->postUpdate($eventArgs);
    }

    private function createNavigation(string $code): NavigationInterface
    {
        $navigation = new Navigation();
        $reflection = new \ReflectionClass($navigation);
        $property = $reflection->getProperty('code');
        $property->setValue($navigation, $code);

        return $navigation;
    }

    private function createItem(?NavigationInterface $navigation): ItemInterface
    {
        $item = new class() extends Item {
        };

        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setLabel('Test Item');

        if (null !== $navigation) {
            $item->setNavigation($navigation);
        }

        return $item;
    }
}
