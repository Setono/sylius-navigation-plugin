<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Cache\CacheItemPoolInterface;
use Setono\SyliusNavigationPlugin\Controller\BuildController;
use Setono\SyliusNavigationPlugin\Manager\ClosureManagerInterface;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Provider\ItemLabelProviderInterface;
use Setono\SyliusNavigationPlugin\Provider\NavigationTreeProviderInterface;
use Setono\SyliusNavigationPlugin\Renderer\CachedNavigationRenderer;
use Setono\SyliusNavigationPlugin\Renderer\NavigationRendererInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class BuildControllerCacheInvalidationTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_invalidates_cache_after_deleting_an_item(): void
    {
        $navigation = $this->createNavigation('main');
        $item = $this->createItem($navigation);

        $closureManager = $this->prophesize(ClosureManagerInterface::class);
        $closureManager->removeTree($item)->shouldBeCalledOnce();

        $cachePool = $this->prophesize(CacheItemPoolInterface::class);
        $cachePool->willImplement(TagAwareCacheInterface::class);
        $cachePool->invalidateTags(['setono_navigation_main'])->shouldBeCalledOnce();

        $cachedRenderer = $this->createCachedRenderer($cachePool->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $controller = new BuildController($managerRegistry->reveal(), $this->prophesize(NavigationTreeProviderInterface::class)->reveal(), $this->prophesize(ItemLabelProviderInterface::class)->reveal());

        $response = $controller->deleteItemAction(
            $navigation,
            $item,
            $closureManager->reveal(),
            $cachedRenderer,
        );

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_deletes_item_successfully_when_caching_is_disabled(): void
    {
        $navigation = $this->createNavigation('main');
        $item = $this->createItem($navigation);

        $closureManager = $this->prophesize(ClosureManagerInterface::class);
        $closureManager->removeTree($item)->shouldBeCalledOnce();

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $controller = new BuildController($managerRegistry->reveal(), $this->prophesize(NavigationTreeProviderInterface::class)->reveal(), $this->prophesize(ItemLabelProviderInterface::class)->reveal());

        $response = $controller->deleteItemAction(
            $navigation,
            $item,
            $closureManager->reveal(),
            null,
        );

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_invalidates_cache_after_reordering_an_item(): void
    {
        $navigation = $this->createNavigation('main');
        $item = $this->createItem($navigation);

        $closureManager = $this->prophesize(ClosureManagerInterface::class);
        $closureManager->moveItem($item, null, 1)->shouldBeCalledOnce();

        $cachePool = $this->prophesize(CacheItemPoolInterface::class);
        $cachePool->willImplement(TagAwareCacheInterface::class);
        $cachePool->invalidateTags(['setono_navigation_main'])->shouldBeCalledOnce();

        $cachedRenderer = $this->createCachedRenderer($cachePool->reveal());

        $repository = $this->prophesize(EntityRepository::class);
        $repository->find(1)->willReturn($item);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->getRepository(ItemInterface::class)->willReturn($repository->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass($navigation::class)->willReturn($entityManager->reveal());

        $controller = new BuildController($managerRegistry->reveal(), $this->prophesize(NavigationTreeProviderInterface::class)->reveal(), $this->prophesize(ItemLabelProviderInterface::class)->reveal());

        $request = new Request(
            content: json_encode([
                'item_id' => 1,
                'new_parent_id' => null,
                'position' => 1,
            ], \JSON_THROW_ON_ERROR),
        );

        $response = $controller->reorderItemAction(
            $request,
            $navigation,
            $closureManager->reveal(),
            $cachedRenderer,
        );

        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_reorders_item_successfully_when_caching_is_disabled(): void
    {
        $navigation = $this->createNavigation('main');
        $item = $this->createItem($navigation);

        $closureManager = $this->prophesize(ClosureManagerInterface::class);
        $closureManager->moveItem($item, null, 1)->shouldBeCalledOnce();

        $repository = $this->prophesize(EntityRepository::class);
        $repository->find(1)->willReturn($item);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->getRepository(ItemInterface::class)->willReturn($repository->reveal());

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass($navigation::class)->willReturn($entityManager->reveal());

        $controller = new BuildController($managerRegistry->reveal(), $this->prophesize(NavigationTreeProviderInterface::class)->reveal(), $this->prophesize(ItemLabelProviderInterface::class)->reveal());

        $request = new Request(
            content: json_encode([
                'item_id' => 1,
                'new_parent_id' => null,
                'position' => 1,
            ], \JSON_THROW_ON_ERROR),
        );

        $response = $controller->reorderItemAction(
            $request,
            $navigation,
            $closureManager->reveal(),
            null,
        );

        self::assertSame(200, $response->getStatusCode());
    }

    private function createNavigation(string $code): NavigationInterface
    {
        $navigation = new Navigation();
        $reflection = new \ReflectionClass($navigation);
        $property = $reflection->getProperty('code');
        $property->setValue($navigation, $code);

        return $navigation;
    }

    private function createItem(NavigationInterface $navigation): ItemInterface
    {
        $item = new class() extends Item {
        };

        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setNavigation($navigation);

        $reflection = new \ReflectionClass(Item::class);
        $property = $reflection->getProperty('id');
        $property->setValue($item, 1);

        return $item;
    }

    private function createCachedRenderer(CacheItemPoolInterface $cachePool): CachedNavigationRenderer
    {
        $decoratedRenderer = $this->prophesize(NavigationRendererInterface::class);
        $channelContext = $this->prophesize(ChannelContextInterface::class);
        $localeContext = $this->prophesize(LocaleContextInterface::class);

        return new CachedNavigationRenderer(
            $decoratedRenderer->reveal(),
            $cachePool,
            $channelContext->reveal(),
            $localeContext->reveal(),
        );
    }
}
