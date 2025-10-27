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

    public function build(NavigationInterface $navigation): iterable
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

        // Return all root nodes (nodes without parents)
        return array_values(array_filter($nodes, static fn (Node $node) => !$node->hasParents()));
    }
}
