<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Graph;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Webmozart\Assert\Assert;

final class GraphBuilder implements GraphBuilderInterface
{
    public function __construct(private readonly ClosureRepositoryInterface $closureRepository)
    {
    }

    public function build(NavigationInterface $navigation, ChannelInterface $channel): iterable
    {
        /** @var array<int, Node> $nodes */
        $nodes = [];

        /** @var array<int, int> $parentMap Maps child ID to parent ID */
        $parentMap = [];

        $closures = $this->closureRepository->findByNavigation($navigation);

        // First pass: Build parent map from depth-1 closures
        foreach ($closures as $closure) {
            if ($closure->getDepth() !== 1) {
                continue;
            }

            $ancestor = $closure->getAncestor();
            $descendant = $closure->getDescendant();

            if ($ancestor !== null && $descendant !== null) {
                $parentMap[(int) $descendant->getId()] = (int) $ancestor->getId();
            }
        }

        // Second pass: Create nodes for enabled items that belong to the channel
        foreach ($closures as $closure) {
            $descendant = $closure->getDescendant();
            Assert::notNull($descendant);

            // Skip disabled items
            if (!$descendant->isEnabled()) {
                continue;
            }

            // Skip items that are restricted to specific channels and the current channel is not one of them
            if (!$descendant->getChannels()->isEmpty() && !$descendant->hasChannel($channel)) {
                continue;
            }

            $descendantId = (int) $descendant->getId();
            if (!isset($nodes[$descendantId])) {
                $nodes[$descendantId] = new Node($descendant);
            }
        }

        // Third pass: Remove nodes that have an excluded ancestor (disabled or not in channel)
        // An item has an excluded ancestor if it has a parent in $parentMap but that parent is not in $nodes
        do {
            $nodesToRemove = [];
            foreach ($nodes as $nodeId => $node) {
                if (isset($parentMap[$nodeId]) && !isset($nodes[$parentMap[$nodeId]])) {
                    $nodesToRemove[] = $nodeId;
                }
            }
            foreach ($nodesToRemove as $nodeId) {
                unset($nodes[$nodeId]);
            }
        } while ([] !== $nodesToRemove);

        // Fourth pass: Establish parent-child relationships
        foreach ($closures as $closure) {
            if ($closure->getDepth() !== 1) {
                continue;
            }

            $ancestor = $closure->getAncestor();
            $descendant = $closure->getDescendant();

            if ($ancestor !== null && $descendant !== null) {
                $ancestorId = (int) $ancestor->getId();
                $descendantId = (int) $descendant->getId();

                if (isset($nodes[$ancestorId], $nodes[$descendantId])) {
                    $nodes[$ancestorId]->addChild($nodes[$descendantId]);
                }
            }
        }

        // Return all root nodes (nodes without parents)
        return array_values(array_filter($nodes, static fn (Node $node) => !$node->hasParents()));
    }
}
