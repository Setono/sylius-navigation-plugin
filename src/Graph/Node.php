<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Graph;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;

/**
 * @implements \IteratorAggregate<int, Node>
 */
final class Node implements \Stringable, \Countable, \IteratorAggregate
{
    /** @var list<Node> */
    private array $parents = [];

    /** @var list<Node> */
    private array $children = [];

    public function __construct(public readonly ItemInterface $item)
    {
    }

    public function __toString(): string
    {
        return (string) $this->item->getLabel();
    }

    /**
     * @return list<Node>
     */
    public function getParents(): array
    {
        return $this->parents;
    }

    /**
     * @phpstan-assert-if-true non-empty-list<Node> $this->parents
     */
    public function hasParents(): bool
    {
        return [] !== $this->parents;
    }

    /**
     * @return list<Node>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(self $child): void
    {
        $child->parents[] = $this;
        $this->children[] = $child;
    }

    public function getDepth(): int
    {
        if (!$this->hasParents()) {
            return 0;
        }

        // get the highest possible depth
        return max(array_map(static fn (Node $node): int => $node->getDepth() + 1, $this->parents));
    }

    public function count(int $mode = \COUNT_NORMAL): int
    {
        if ($mode === \COUNT_RECURSIVE) {
            return count($this->children) + array_sum(array_map(static fn (Node $node): int => $node->count($mode), $this->children));
        }

        return count($this->children);
    }

    /**
     * @return \ArrayIterator<int, Node>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->children);
    }
}
