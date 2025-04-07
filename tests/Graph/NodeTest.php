<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Graph;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Graph\Node;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;

final class NodeTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_adds_a_child(): void
    {
        $parent = $this->createNode('Parent');
        $child = $this->createNode('Child');

        $parent->addChild($child);

        self::assertContains($child, $parent->getChildren());
        self::assertContains($parent, $child->getParents());
    }

    /** @test */
    public function it_calculates_depth(): void
    {
        $root = $this->createNode('Root');
        $child1 = $this->createNode('Child 1');
        $child2 = $this->createNode('Child 2');

        $root->addChild($child1);
        $child1->addChild($child2);

        self::assertSame(0, $root->getDepth());
        self::assertSame(1, $child1->getDepth());
        self::assertSame(2, $child2->getDepth());
    }

    /** @test */
    public function it_checks_if_node_has_parents(): void
    {
        $node = $this->createNode('Node');
        $parent = $this->createNode('Parent');

        self::assertFalse($node->hasParents());

        $parent->addChild($node);

        self::assertTrue($node->hasParents());
    }

    /** @test */
    public function it_counts(): void
    {
        $parent = $this->createNode('Parent');
        $child1 = $this->createNode('Child 1');
        $child2 = $this->createNode('Child 2');

        $parent->addChild($child1);
        $parent->addChild($child2);

        self::assertCount(2, $parent);
    }

    /** @test */
    public function it_counts_recursively(): void
    {
        $parent = $this->createNode('Parent');
        $child1 = $this->createNode('Child 1');
        $child2 = $this->createNode('Child 2');
        $grandchild1 = $this->createNode('Grandchild 1');
        $grandchild2 = $this->createNode('Grandchild 2');

        // Constructing the recursive tree:
        $parent->addChild($child1);
        $parent->addChild($child2);
        $child1->addChild($grandchild1);
        $child2->addChild($grandchild2);

        self::assertSame(4, $parent->count(\COUNT_RECURSIVE));
    }

    /** @test */
    public function it_iterates_over_children(): void
    {
        $parent = $this->createNode('Parent');
        $child1 = $this->createNode('Child 1');
        $child2 = $this->createNode('Child 2');

        $parent->addChild($child1);
        $parent->addChild($child2);

        $children = iterator_to_array($parent->getIterator());
        self::assertSame([$child1, $child2], $children);
    }

    /** @test */
    public function it_converts_to_string(): void
    {
        $node = $this->createNode('Node');

        self::assertSame('Node', (string) $node);
    }

    private function createNode(string $label): Node
    {
        $item = $this->prophesize(ItemInterface::class);
        $item->getLabel()->willReturn($label);

        return new Node($item->reveal());
    }
}
