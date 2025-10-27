<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Graph;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusNavigationPlugin\Graph\GraphBuilder;
use Setono\SyliusNavigationPlugin\Graph\Node;
use Setono\SyliusNavigationPlugin\Model\Closure;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;

final class GraphBuilderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ClosureRepositoryInterface> */
    private ObjectProphecy $closureRepository;

    private GraphBuilder $graphBuilder;

    private NavigationInterface $navigation;

    protected function setUp(): void
    {
        $this->closureRepository = $this->prophesize(ClosureRepositoryInterface::class);
        $this->graphBuilder = new GraphBuilder($this->closureRepository->reveal());
        $this->navigation = new Navigation();
    }

    /**
     * @test
     */
    public function it_builds_empty_graph_when_no_closures(): void
    {
        $this->closureRepository->findByNavigation($this->navigation)->willReturn([]);

        $nodes = $this->graphBuilder->build($this->navigation);

        self::assertIsIterable($nodes);
        self::assertEmpty($nodes);
    }

    /**
     * @test
     */
    public function it_builds_graph_with_single_root_node(): void
    {
        $item = $this->createItem(1, 'Root');

        $closure = new Closure();
        $closure->setDescendant($item);
        $closure->setDepth(0);

        $this->closureRepository->findByNavigation($this->navigation)->willReturn([$closure]);

        $nodes = $this->graphBuilder->build($this->navigation);

        self::assertIsArray($nodes);
        self::assertCount(1, $nodes);
        self::assertInstanceOf(Node::class, $nodes[0]);
        self::assertSame($item, $nodes[0]->item);
        self::assertFalse($nodes[0]->hasParents());
        self::assertEmpty($nodes[0]->getChildren());
    }

    /**
     * @test
     */
    public function it_builds_graph_with_parent_child_relationship(): void
    {
        $parent = $this->createItem(1, 'Parent');
        $child = $this->createItem(2, 'Child');

        // Self-referencing closures (depth 0)
        $parentClosure = new Closure();
        $parentClosure->setDescendant($parent);
        $parentClosure->setDepth(0);

        $childClosure = new Closure();
        $childClosure->setDescendant($child);
        $childClosure->setDepth(0);

        // Parent-child relationship closure (depth 1)
        $relationshipClosure = new Closure();
        $relationshipClosure->setAncestor($parent);
        $relationshipClosure->setDescendant($child);
        $relationshipClosure->setDepth(1);

        $this->closureRepository->findByNavigation($this->navigation)->willReturn([
            $parentClosure,
            $childClosure,
            $relationshipClosure,
        ]);

        $nodes = $this->graphBuilder->build($this->navigation);

        self::assertIsArray($nodes);
        self::assertCount(1, $nodes, 'Should return only root nodes');

        $rootNode = $nodes[0];
        self::assertSame($parent, $rootNode->item);
        self::assertFalse($rootNode->hasParents());
        self::assertCount(1, $rootNode->getChildren());

        $childNode = $rootNode->getChildren()[0];
        self::assertSame($child, $childNode->item);
        self::assertTrue($childNode->hasParents());
    }

    /**
     * @test
     */
    public function it_builds_graph_with_multiple_root_nodes(): void
    {
        $root1 = $this->createItem(1, 'Root 1');
        $root2 = $this->createItem(2, 'Root 2');

        $closure1 = new Closure();
        $closure1->setDescendant($root1);
        $closure1->setDepth(0);

        $closure2 = new Closure();
        $closure2->setDescendant($root2);
        $closure2->setDepth(0);

        $this->closureRepository->findByNavigation($this->navigation)->willReturn([
            $closure1,
            $closure2,
        ]);

        $nodes = $this->graphBuilder->build($this->navigation);

        self::assertIsArray($nodes);
        self::assertCount(2, $nodes);
        self::assertSame($root1, $nodes[0]->item);
        self::assertSame($root2, $nodes[1]->item);
        self::assertFalse($nodes[0]->hasParents());
        self::assertFalse($nodes[1]->hasParents());
    }

    /**
     * @test
     */
    public function it_builds_graph_with_deep_hierarchy(): void
    {
        $root = $this->createItem(1, 'Root');
        $child = $this->createItem(2, 'Child');
        $grandchild = $this->createItem(3, 'Grandchild');

        // Self-referencing closures
        $rootClosure = new Closure();
        $rootClosure->setDescendant($root);
        $rootClosure->setDepth(0);

        $childClosure = new Closure();
        $childClosure->setDescendant($child);
        $childClosure->setDepth(0);

        $grandchildClosure = new Closure();
        $grandchildClosure->setDescendant($grandchild);
        $grandchildClosure->setDepth(0);

        // Direct relationships (depth 1)
        $rootToChild = new Closure();
        $rootToChild->setAncestor($root);
        $rootToChild->setDescendant($child);
        $rootToChild->setDepth(1);

        $childToGrandchild = new Closure();
        $childToGrandchild->setAncestor($child);
        $childToGrandchild->setDescendant($grandchild);
        $childToGrandchild->setDepth(1);

        // Transitive closure (depth 2) - should be ignored by builder
        $rootToGrandchild = new Closure();
        $rootToGrandchild->setAncestor($root);
        $rootToGrandchild->setDescendant($grandchild);
        $rootToGrandchild->setDepth(2);

        $this->closureRepository->findByNavigation($this->navigation)->willReturn([
            $rootClosure,
            $childClosure,
            $grandchildClosure,
            $rootToChild,
            $childToGrandchild,
            $rootToGrandchild,
        ]);

        $nodes = $this->graphBuilder->build($this->navigation);

        self::assertIsArray($nodes);
        self::assertCount(1, $nodes, 'Should return only root node');

        $rootNode = $nodes[0];
        self::assertSame($root, $rootNode->item);
        self::assertCount(1, $rootNode->getChildren());

        $childNode = $rootNode->getChildren()[0];
        self::assertSame($child, $childNode->item);
        self::assertCount(1, $childNode->getChildren());

        $grandchildNode = $childNode->getChildren()[0];
        self::assertSame($grandchild, $grandchildNode->item);
        self::assertEmpty($grandchildNode->getChildren());
    }

    /**
     * @test
     */
    public function it_ignores_closures_with_depth_greater_than_one(): void
    {
        $item1 = $this->createItem(1, 'Item 1');
        $item2 = $this->createItem(2, 'Item 2');
        $item3 = $this->createItem(3, 'Item 3');

        // Self-referencing closures
        $closure1 = new Closure();
        $closure1->setDescendant($item1);
        $closure1->setDepth(0);

        $closure2 = new Closure();
        $closure2->setDescendant($item2);
        $closure2->setDepth(0);

        $closure3 = new Closure();
        $closure3->setDescendant($item3);
        $closure3->setDepth(0);

        // Transitive closure with depth 2 (should be ignored)
        $transitiveClosure = new Closure();
        $transitiveClosure->setAncestor($item1);
        $transitiveClosure->setDescendant($item3);
        $transitiveClosure->setDepth(2);

        $this->closureRepository->findByNavigation($this->navigation)->willReturn([
            $closure1,
            $closure2,
            $closure3,
            $transitiveClosure,
        ]);

        $nodes = $this->graphBuilder->build($this->navigation);

        self::assertIsArray($nodes);
        // All three items should be root nodes since depth 2 closure is ignored
        self::assertCount(3, $nodes);
        self::assertEmpty($nodes[0]->getChildren());
        self::assertEmpty($nodes[1]->getChildren());
        self::assertEmpty($nodes[2]->getChildren());
    }

    /**
     * @test
     */
    public function it_builds_graph_with_siblings(): void
    {
        $parent = $this->createItem(1, 'Parent');
        $child1 = $this->createItem(2, 'Child 1');
        $child2 = $this->createItem(3, 'Child 2');

        // Self-referencing closures
        $parentClosure = new Closure();
        $parentClosure->setDescendant($parent);
        $parentClosure->setDepth(0);

        $child1Closure = new Closure();
        $child1Closure->setDescendant($child1);
        $child1Closure->setDepth(0);

        $child2Closure = new Closure();
        $child2Closure->setDescendant($child2);
        $child2Closure->setDepth(0);

        // Parent-child relationships
        $parentToChild1 = new Closure();
        $parentToChild1->setAncestor($parent);
        $parentToChild1->setDescendant($child1);
        $parentToChild1->setDepth(1);

        $parentToChild2 = new Closure();
        $parentToChild2->setAncestor($parent);
        $parentToChild2->setDescendant($child2);
        $parentToChild2->setDepth(1);

        $this->closureRepository->findByNavigation($this->navigation)->willReturn([
            $parentClosure,
            $child1Closure,
            $child2Closure,
            $parentToChild1,
            $parentToChild2,
        ]);

        $nodes = $this->graphBuilder->build($this->navigation);

        self::assertIsArray($nodes);
        self::assertCount(1, $nodes, 'Should return only root node');

        $rootNode = $nodes[0];
        self::assertSame($parent, $rootNode->item);
        self::assertCount(2, $rootNode->getChildren());

        $children = $rootNode->getChildren();
        self::assertSame($child1, $children[0]->item);
        self::assertSame($child2, $children[1]->item);
    }

    /**
     * @test
     */
    public function it_handles_closures_with_null_ancestor(): void
    {
        $item = $this->createItem(1, 'Item');

        $validClosure = new Closure();
        $validClosure->setDescendant($item);
        $validClosure->setDepth(0);

        // Closure with null ancestor (depth 1 requires ancestor for relationship)
        $nullAncestorClosure = new Closure();
        $nullAncestorClosure->setAncestor(null);
        $nullAncestorClosure->setDescendant($item);
        $nullAncestorClosure->setDepth(1);

        $this->closureRepository->findByNavigation($this->navigation)->willReturn([
            $validClosure,
            $nullAncestorClosure,
        ]);

        $nodes = $this->graphBuilder->build($this->navigation);

        self::assertIsArray($nodes);
        // Should create one node from the valid closure, and it should remain a root node
        // because the relationship with null ancestor is skipped
        self::assertCount(1, $nodes);
        self::assertSame($item, $nodes[0]->item);
        self::assertFalse($nodes[0]->hasParents());
    }

    private function createItem(int $id, string $label): ItemInterface
    {
        $item = new class() extends Item {
        };

        // Use reflection to set the ID since it's protected
        $reflection = new \ReflectionClass($item);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($item, $id);

        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setLabel($label);

        return $item;
    }
}
