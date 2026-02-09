<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Renderer\CachedNavigationRenderer;
use Setono\SyliusNavigationPlugin\Repository\TaxonItemRepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class TaxonBasedNavigationCacheInvalidatorListener
{
    /** @var array<int, list<NavigationInterface>> */
    private array $pendingInvalidations = [];

    public function __construct(
        private readonly CachedNavigationRenderer $cachedRenderer,
        private readonly TaxonItemRepositoryInterface $taxonItemRepository,
    ) {
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->invalidateCache($args);
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $obj = $args->getObject();

        if (!$obj instanceof TaxonInterface) {
            return;
        }

        // Capture affected navigations before the entity loses its identifier
        $navigations = $this->getAffectedNavigations($obj);

        if ([] !== $navigations) {
            $this->pendingInvalidations[spl_object_id($obj)] = $navigations;
        }
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $obj = $args->getObject();

        if (!$obj instanceof TaxonInterface) {
            return;
        }

        $oid = spl_object_id($obj);
        $navigations = $this->pendingInvalidations[$oid] ?? [];
        unset($this->pendingInvalidations[$oid]);

        if ([] !== $navigations) {
            $this->cachedRenderer->invalidate(...$navigations);
        }
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

        $navigations = $this->getAffectedNavigations($obj);

        if ([] !== $navigations) {
            $this->cachedRenderer->invalidate(...$navigations);
        }
    }

    /**
     * @return list<NavigationInterface>
     */
    private function getAffectedNavigations(TaxonInterface $taxon): array
    {
        return array_values(array_filter(
            array_map(
                static fn (TaxonItemInterface $item) => $item->getNavigation(),
                $this->taxonItemRepository->findByTaxon($taxon),
            ),
        ));
    }
}
