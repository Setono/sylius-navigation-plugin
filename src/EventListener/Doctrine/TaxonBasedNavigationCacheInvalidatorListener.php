<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Renderer\CachedNavigationRenderer;
use Setono\SyliusNavigationPlugin\Repository\TaxonItemRepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class TaxonBasedNavigationCacheInvalidatorListener
{
    public function __construct(
        private readonly CachedNavigationRenderer $cachedRenderer,
        private readonly TaxonItemRepositoryInterface $taxonItemRepository,
    ) {
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->invalidateCache($args);
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->invalidateCache($args);
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->invalidateCache($args);
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    private function invalidateCache(LifecycleEventArgs $args): void
    {
        $obj = $args->getObject();

        if (!$obj instanceof TaxonInterface) {
            return;
        }

        // Filter out navigations that are currently being built
        // Cache will be invalidated once when build completes
        $navigations = array_filter(
            array_map(
                static fn (TaxonItemInterface $item) => $item->getNavigation(),
                $this->taxonItemRepository->findByTaxon($obj),
            ),
            static fn (?NavigationInterface $navigation): bool => null !== $navigation &&
                $navigation->getState() !== NavigationInterface::STATE_BUILDING,
        );

        if ([] !== $navigations) {
            $this->cachedRenderer->invalidate(...$navigations);
        }
    }
}
