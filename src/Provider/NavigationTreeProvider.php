<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Provider;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistryInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

final class NavigationTreeProvider implements NavigationTreeProviderInterface
{
    public function __construct(
        private readonly ClosureRepositoryInterface $closureRepository,
        private readonly ItemTypeRegistryInterface $itemTypeRegistry,
        private readonly ItemLabelProviderInterface $itemLabelProvider,
    ) {
    }

    public function getTree(NavigationInterface $navigation, bool $recursive = true, ?ChannelInterface $channel = null): array
    {
        $rootItems = $this->closureRepository->findRootItems($navigation);
        $tree = [];

        foreach ($rootItems as $rootItem) {
            $tree[] = $this->buildItemNode($rootItem, $recursive, $channel);
        }

        return $tree;
    }

    public function getChildren(ItemInterface $item, bool $recursive = true, ?ChannelInterface $channel = null): array
    {
        $children = $this->closureRepository->findDirectChildren($item);
        $tree = [];

        foreach ($children as $child) {
            $tree[] = $this->buildItemNode($child, $recursive, $channel);
        }

        return $tree;
    }

    public function searchItems(NavigationInterface $navigation, string $searchTerm): array
    {
        $rootItems = $this->closureRepository->findRootItems($navigation);
        $matchingItems = [];

        foreach ($rootItems as $rootItem) {
            $matchingItems = array_merge($matchingItems, $this->searchItemsRecursive($rootItem, $searchTerm));
        }

        $nodeIds = [];
        foreach ($matchingItems as $item) {
            $nodeIds[] = (string) $item->getId();

            $parentClosures = $this->closureRepository->findBy([
                'descendant' => $item,
            ]);

            foreach ($parentClosures as $closure) {
                $ancestor = $closure->getAncestor();
                if ($ancestor && $ancestor !== $item) {
                    $parentId = (string) $ancestor->getId();
                    if (!in_array($parentId, $nodeIds, true)) {
                        $nodeIds[] = $parentId;
                    }
                }
            }
        }

        return $nodeIds;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildItemNode(ItemInterface $item, bool $recursive, ?ChannelInterface $channel): array
    {
        $children = $this->closureRepository->findDirectChildren($item);
        $hasChildren = count($children) > 0;
        $childrenArray = [];

        if ($recursive) {
            foreach ($children as $child) {
                $childrenArray[] = $this->buildItemNode($child, true, $channel);
            }
        }

        $itemType = $this->itemTypeRegistry->getByEntity($item::class);

        $isChannelHidden = $channel !== null && !$item->hasChannel($channel);

        $liClasses = [];
        if (!$item->isEnabled()) {
            $liClasses[] = 'item-disabled';
        }
        if ($isChannelHidden) {
            $liClasses[] = 'item-channel-hidden';
        }

        $locale = null;
        if ($channel instanceof \Sylius\Component\Core\Model\ChannelInterface) {
            $locale = $channel->getDefaultLocale()?->getCode();
        }

        $node = [
            'id' => (string) $item->getId(),
            'text' => $this->itemLabelProvider->getLabel($item, $locale),
            'type' => $itemType->name,
            'state' => [
                'opened' => false,
            ],
            'li_attr' => [
                'class' => implode(' ', $liClasses),
            ],
            'a_attr' => [
                'data-enabled' => $item->isEnabled() ? 'true' : 'false',
                'data-item-type' => $itemType->name,
            ],
            'data' => [
                'enabled' => $item->isEnabled(),
                'taxon_id' => $item instanceof TaxonItemInterface ? $item->getTaxon()?->getId() : null,
                'item_type' => $itemType->name,
            ],
        ];

        if ($recursive) {
            $node['children'] = $childrenArray;
        } else {
            $node['children'] = $hasChildren;
        }

        return $node;
    }

    /**
     * @return list<ItemInterface>
     */
    private function searchItemsRecursive(ItemInterface $item, string $searchTerm): array
    {
        $matches = [];
        $children = $this->closureRepository->findDirectChildren($item);

        foreach ($children as $child) {
            $label = $this->itemLabelProvider->getLabel($child);
            if ($label && stripos($label, $searchTerm) !== false) {
                $matches[] = $child;
            }

            $childMatches = $this->searchItemsRecursive($child, $searchTerm);
            $matches = array_merge($matches, $childMatches);
        }

        return $matches;
    }
}
