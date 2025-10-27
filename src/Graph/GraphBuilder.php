<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Graph;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Webmozart\Assert\Assert;

final class GraphBuilder implements GraphBuilderInterface
{
    public function __construct(private readonly ClosureRepositoryInterface $closureRepository)
    {
    }

    public function build(NavigationInterface $navigation): Node
    {
        /** @var array<int, Node> $nodes */
        $nodes = [];

        $closures = $this->closureRepository->findByNavigation($navigation);
        foreach ($closures as $closure) {
            $descendant = $closure->getDescendant();
            Assert::notNull($descendant);

            $descendantId = (int) $descendant->getId();
            if (!isset($nodes[$descendantId])) {
                $nodes[$descendantId] = new Node($descendant);
            }
        }

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

        // Find all root nodes (nodes without parents)
        $roots = array_values(array_filter($nodes, static fn (Node $node) => !$node->hasParents()));

        // Create a virtual root node that contains all actual root nodes as children
        $virtualRoot = new Node(null);
        foreach ($roots as $root) {
            $virtualRoot->addChild($root);
        }

        return $virtualRoot;
    }
}
