<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Functional\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusNavigationPlugin\Model\ClosureInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ClosureRepositoryTest extends KernelTestCase
{
    private ClosureRepositoryInterface $closureRepository;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $this->closureRepository = $container->get('setono_sylius_navigation.repository.closure');
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    /**
     * @test
     */
    public function it_finds_ancestors_for_an_item(): void
    {
        $navigation = $this->createNavigation('ancestors_test');
        $root = $this->createItem($navigation);
        $child = $this->createItem($navigation);
        $grandchild = $this->createItem($navigation);

        // Self-references
        $this->createClosure($root, $root, 0);
        $this->createClosure($child, $child, 0);
        $this->createClosure($grandchild, $grandchild, 0);

        // Parent-child
        $this->createClosure($root, $child, 1);
        $this->createClosure($child, $grandchild, 1);

        // Grandparent-grandchild
        $this->createClosure($root, $grandchild, 2);

        $this->entityManager->flush();

        $ancestors = $this->closureRepository->findAncestors($grandchild);

        self::assertCount(3, $ancestors);

        $depths = array_map(static fn (ClosureInterface $c): int => $c->getDepth(), $ancestors);
        sort($depths);
        self::assertSame([0, 1, 2], $depths);
    }

    /**
     * @test
     */
    public function it_returns_empty_ancestors_for_item_with_no_closures(): void
    {
        $navigation = $this->createNavigation('no_ancestors');
        $item = $this->createItem($navigation);

        $this->entityManager->flush();

        $ancestors = $this->closureRepository->findAncestors($item);

        self::assertSame([], $ancestors);
    }

    /**
     * @test
     */
    public function it_finds_graph_for_root_item(): void
    {
        $navigation = $this->createNavigation('graph_test');
        $root = $this->createItem($navigation);
        $childA = $this->createItem($navigation);
        $childB = $this->createItem($navigation);

        $this->createClosure($root, $root, 0);
        $this->createClosure($childA, $childA, 0);
        $this->createClosure($childB, $childB, 0);
        $this->createClosure($root, $childA, 1);
        $this->createClosure($root, $childB, 1);

        $this->entityManager->flush();

        $graph = $this->closureRepository->findGraph($root);

        // Should include all closures reachable from root:
        // root->root(0), root->childA(1), root->childB(1), childA->childA(0), childB->childB(0)
        self::assertCount(5, $graph);
    }

    /**
     * @test
     */
    public function it_finds_closures_by_navigation(): void
    {
        $nav1 = $this->createNavigation('by_nav_1');
        $nav2 = $this->createNavigation('by_nav_2');

        $item1 = $this->createItem($nav1);
        $item2 = $this->createItem($nav1);
        $item3 = $this->createItem($nav2);

        $this->createClosure($item1, $item1, 0);
        $this->createClosure($item2, $item2, 0);
        $this->createClosure($item1, $item2, 1);
        $this->createClosure($item3, $item3, 0);

        $this->entityManager->flush();

        $closures = $this->closureRepository->findByNavigation($nav1);

        self::assertCount(3, $closures);

        foreach ($closures as $closure) {
            $descendant = $closure->getDescendant();
            self::assertNotNull($descendant);
            self::assertSame($nav1->getId(), $descendant->getNavigation()?->getId());
        }
    }

    /**
     * @test
     */
    public function it_finds_root_items_for_navigation(): void
    {
        $navigation = $this->createNavigation('root_items');
        $root1 = $this->createItem($navigation, 0);
        $root2 = $this->createItem($navigation, 1);
        $child = $this->createItem($navigation, 0);

        // Self-references for all
        $this->createClosure($root1, $root1, 0);
        $this->createClosure($root2, $root2, 0);
        $this->createClosure($child, $child, 0);

        // child is under root1
        $this->createClosure($root1, $child, 1);

        $this->entityManager->flush();

        $rootItems = $this->closureRepository->findRootItems($navigation);

        self::assertCount(2, $rootItems);

        $rootItemIds = array_map(static fn (ItemInterface $i): ?int => $i->getId(), $rootItems);
        self::assertContains($root1->getId(), $rootItemIds);
        self::assertContains($root2->getId(), $rootItemIds);
        self::assertNotContains($child->getId(), $rootItemIds);
    }

    /**
     * @test
     */
    public function it_returns_root_items_ordered_by_position(): void
    {
        $navigation = $this->createNavigation('root_items_order');
        $itemA = $this->createItem($navigation, 2);
        $itemB = $this->createItem($navigation, 0);
        $itemC = $this->createItem($navigation, 1);

        $this->createClosure($itemA, $itemA, 0);
        $this->createClosure($itemB, $itemB, 0);
        $this->createClosure($itemC, $itemC, 0);

        $this->entityManager->flush();

        $rootItems = $this->closureRepository->findRootItems($navigation);

        self::assertCount(3, $rootItems);
        self::assertSame(0, $rootItems[0]->getPosition());
        self::assertSame(1, $rootItems[1]->getPosition());
        self::assertSame(2, $rootItems[2]->getPosition());
    }

    /**
     * @test
     */
    public function it_returns_empty_root_items_for_navigation_with_no_items(): void
    {
        $navigation = $this->createNavigation('empty_nav');

        $this->entityManager->flush();

        $rootItems = $this->closureRepository->findRootItems($navigation);

        self::assertSame([], $rootItems);
    }

    private function createNavigation(string $code): NavigationInterface
    {
        $factory = self::getContainer()->get('setono_sylius_navigation.factory.navigation');
        /** @var NavigationInterface $navigation */
        $navigation = $factory->createNew();
        $navigation->setCode($code);

        $this->entityManager->persist($navigation);
        $this->entityManager->flush();

        return $navigation;
    }

    private function createItem(NavigationInterface $navigation, int $position = 0): ItemInterface
    {
        $factory = self::getContainer()->get('setono_sylius_navigation.factory.item');
        /** @var ItemInterface $item */
        $item = $factory->createNew();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setNavigation($navigation);
        $item->setPosition($position);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }

    private function createClosure(ItemInterface $ancestor, ItemInterface $descendant, int $depth): ClosureInterface
    {
        $factory = self::getContainer()->get('setono_sylius_navigation.factory.closure');
        /** @var ClosureInterface $closure */
        $closure = $factory->createNew();
        $closure->setAncestor($ancestor);
        $closure->setDescendant($descendant);
        $closure->setDepth($depth);

        $this->entityManager->persist($closure);

        return $closure;
    }
}
