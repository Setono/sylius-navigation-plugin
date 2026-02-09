<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Cache\CacheItemPoolInterface;
use Setono\SyliusNavigationPlugin\EventListener\Doctrine\TaxonBasedNavigationCacheInvalidatorListener;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItem;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Renderer\CachedNavigationRenderer;
use Setono\SyliusNavigationPlugin\Renderer\NavigationRendererInterface;
use Setono\SyliusNavigationPlugin\Repository\TaxonItemRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class TaxonBasedNavigationCacheInvalidatorListenerTest extends TestCase
{
    use ProphecyTrait;

    private CachedNavigationRenderer $cachedRenderer;

    private ObjectProphecy $cachePool;

    private ObjectProphecy $taxonItemRepository;

    private TaxonBasedNavigationCacheInvalidatorListener $listener;

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

        $this->taxonItemRepository = $this->prophesize(TaxonItemRepositoryInterface::class);

        $this->listener = new TaxonBasedNavigationCacheInvalidatorListener(
            $this->cachedRenderer,
            $this->taxonItemRepository->reveal(),
        );
    }

    /**
     * @test
     */
    public function it_invalidates_cache_on_post_update_when_taxon_is_updated(): void
    {
        $taxon = $this->createTaxon('category');
        $navigation = $this->createNavigation('main-menu');
        $taxonItem = $this->createTaxonItem($taxon, $navigation);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostUpdateEventArgs($taxon, $entityManager);

        $this->taxonItemRepository->findByTaxon($taxon)->willReturn([$taxonItem]);
        $this->cachePool->invalidateTags(['setono_navigation_main-menu'])->shouldBeCalledOnce();

        $this->listener->postUpdate($eventArgs);
    }

    /**
     * @test
     */
    public function it_invalidates_cache_on_post_remove_when_taxon_is_removed(): void
    {
        $taxon = $this->createTaxon('category');
        $navigation = $this->createNavigation('main-menu');
        $taxonItem = $this->createTaxonItem($taxon, $navigation);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $preRemoveArgs = new PreRemoveEventArgs($taxon, $entityManager);
        $postRemoveArgs = new PostRemoveEventArgs($taxon, $entityManager);

        $this->taxonItemRepository->findByTaxon($taxon)->willReturn([$taxonItem]);
        $this->cachePool->invalidateTags(['setono_navigation_main-menu'])->shouldBeCalledOnce();

        // preRemove captures affected navigations while the entity still has its identifier
        $this->listener->preRemove($preRemoveArgs);
        // postRemove uses captured data to invalidate cache
        $this->listener->postRemove($postRemoveArgs);
    }

    /**
     * @test
     */
    public function it_invalidates_cache_on_post_persist_when_taxon_is_created(): void
    {
        $taxon = $this->createTaxon('category');
        $navigation = $this->createNavigation('main-menu');
        $taxonItem = $this->createTaxonItem($taxon, $navigation);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostPersistEventArgs($taxon, $entityManager);

        $this->taxonItemRepository->findByTaxon($taxon)->willReturn([$taxonItem]);
        $this->cachePool->invalidateTags(['setono_navigation_main-menu'])->shouldBeCalledOnce();

        $this->listener->postPersist($eventArgs);
    }

    /**
     * @test
     */
    public function it_invalidates_multiple_navigations_when_taxon_is_used_in_multiple_items(): void
    {
        $taxon = $this->createTaxon('category');
        $navigation1 = $this->createNavigation('main-menu');
        $navigation2 = $this->createNavigation('footer-menu');
        $taxonItem1 = $this->createTaxonItem($taxon, $navigation1);
        $taxonItem2 = $this->createTaxonItem($taxon, $navigation2);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostUpdateEventArgs($taxon, $entityManager);

        $this->taxonItemRepository->findByTaxon($taxon)->willReturn([$taxonItem1, $taxonItem2]);
        // With variadic invalidate, all tags are invalidated in a single call
        $this->cachePool->invalidateTags(['setono_navigation_main-menu', 'setono_navigation_footer-menu'])->shouldBeCalledOnce();

        $this->listener->postUpdate($eventArgs);
    }

    /**
     * @test
     */
    public function it_does_not_invalidate_cache_when_no_taxon_items_reference_the_taxon(): void
    {
        $taxon = $this->createTaxon('category');

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostUpdateEventArgs($taxon, $entityManager);

        $this->taxonItemRepository->findByTaxon($taxon)->willReturn([]);
        $this->cachePool->invalidateTags(Argument::any())->shouldNotBeCalled();
        $this->cachePool->clear()->shouldNotBeCalled();

        $this->listener->postUpdate($eventArgs);
    }

    /**
     * @test
     */
    public function it_does_not_invalidate_cache_when_taxon_item_has_no_navigation(): void
    {
        $taxon = $this->createTaxon('category');
        $taxonItem = $this->createTaxonItem($taxon, null);

        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new PostUpdateEventArgs($taxon, $entityManager);

        $this->taxonItemRepository->findByTaxon($taxon)->willReturn([$taxonItem]);
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

        $this->taxonItemRepository->findByTaxon(Argument::any())->shouldNotBeCalled();
        $this->cachePool->invalidateTags(Argument::any())->shouldNotBeCalled();
        $this->cachePool->clear()->shouldNotBeCalled();

        $this->listener->postUpdate($eventArgs);
    }

    private function createTaxon(string $code): TaxonInterface
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getCode()->willReturn($code);

        return $taxon->reveal();
    }

    private function createNavigation(string $code): NavigationInterface
    {
        $navigation = new Navigation();
        $reflection = new \ReflectionClass($navigation);
        $property = $reflection->getProperty('code');
        $property->setValue($navigation, $code);

        return $navigation;
    }

    private function createTaxonItem(TaxonInterface $taxon, ?NavigationInterface $navigation): TaxonItemInterface
    {
        $taxonItem = new TaxonItem();
        $taxonItem->setCurrentLocale('en_US');
        $taxonItem->setFallbackLocale('en_US');
        $taxonItem->setTaxon($taxon);

        if (null !== $navigation) {
            $taxonItem->setNavigation($navigation);
        }

        return $taxonItem;
    }
}
