<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Renderer\CachedNavigationRenderer;

final class ItemBasedNavigationCacheInvalidatorListener
{
    public function __construct(private readonly CachedNavigationRenderer $cachedRenderer)
    {
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

        if ($obj instanceof ItemInterface) {
            $obj = $obj->getNavigation();

            // Skip cache invalidation if navigation is being built
            // Cache will be invalidated once when build completes
            if ($obj instanceof NavigationInterface && $obj->getState() === NavigationInterface::STATE_BUILDING) {
                return;
            }
        }

        $obj instanceof NavigationInterface && $this->cachedRenderer->invalidate($obj);
    }
}
