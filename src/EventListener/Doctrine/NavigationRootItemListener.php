<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Setono\SyliusNavigationPlugin\Factory\ItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Manager\ClosureManagerInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;

final class NavigationRootItemListener
{
    /** @var array<int, ItemInterface> */
    private array $pendingNavigations = [];

    public function __construct(
        private readonly ItemFactoryInterface $itemFactory,
        private readonly ClosureManagerInterface $closureManager,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof NavigationInterface) {
            return;
        }

        // If the navigation already has a root item, skip
        if ($entity->getRootItem() !== null) {
            return;
        }

        // Create a hidden root item
        $hiddenRoot = $this->itemFactory->createNew();
        $hiddenRoot->setLabel('__ROOT__'); // Hidden label that won't be displayed
        $hiddenRoot->setEnabled(false); // Disabled so it won't appear in frontend navigation

        // Set it as the navigation's root item (cascade: persist will handle persistence)
        $entity->setRootItem($hiddenRoot);

        // Store for postPersist processing
        $this->pendingNavigations[spl_object_id($entity)] = $hiddenRoot;
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof NavigationInterface) {
            return;
        }

        $entityId = spl_object_id($entity);
        if (!isset($this->pendingNavigations[$entityId])) {
            return;
        }

        $hiddenRoot = $this->pendingNavigations[$entityId];
        unset($this->pendingNavigations[$entityId]);

        // Now create closure for the root item (entities have IDs now)
        $this->closureManager->createItem($hiddenRoot);
    }
}
