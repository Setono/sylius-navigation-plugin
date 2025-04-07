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
        $rootItem = $navigation->getRootItem();
        Assert::notNull($rootItem);

        /** @var array<int, Node> $nodes */
        $nodes = [];

        $closures = $this->closureRepository->findGraph($rootItem);
        foreach ($closures as $closure) {
            $descendant = $closure->getDescendant();
            Assert::notNull($descendant);

            $nodes[(int) $descendant->getId()] = new Node($descendant);
        }

        foreach ($closures as $closure) {
            if ($closure->getDepth() !== 1) {
                continue;
            }

            $ancestor = (int) $closure->getAncestor()?->getId();
            $descendant = (int) $closure->getDescendant()?->getId();

            $nodes[$ancestor]->addChild($nodes[$descendant]);
        }

        $roots = array_values(array_filter($nodes, static fn (Node $node) => !$node->hasParents()));

        Assert::count($roots, 1, 'There should be exactly one root node');

        return $roots[0];
    }
}
