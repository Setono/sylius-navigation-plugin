<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Functional\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusNavigationPlugin\Manager\ClosureManagerInterface;
use Setono\SyliusNavigationPlugin\Model\ClosureInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ClosureManagerTest extends KernelTestCase
{
    private ClosureManagerInterface $closureManager;

    private ClosureRepositoryInterface $closureRepository;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $this->closureManager = $container->get(\Setono\SyliusNavigationPlugin\Manager\ClosureManager::class);
        $this->closureRepository = $container->get('setono_sylius_navigation.repository.closure');
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    /**
     * @test
     */
    public function it_creates_self_closure_for_root_item(): void
    {
        $navigation = $this->createNavigation('create_root');
        $item = $this->createItem($navigation);

        $this->closureManager->createItem($item);

        $closures = $this->closureRepository->findAncestors($item);

        self::assertCount(1, $closures);
        self::assertSame(0, $closures[0]->getDepth());
        self::assertSame($item->getId(), $closures[0]->getAncestor()?->getId());
        self::assertSame($item->getId(), $closures[0]->getDescendant()?->getId());
    }

    /**
     * @test
     */
    public function it_creates_closures_for_child_item(): void
    {
        $navigation = $this->createNavigation('create_child');
        $parent = $this->createItem($navigation);
        $child = $this->createItem($navigation);

        $this->closureManager->createItem($parent);
        $this->closureManager->createItem($child, $parent);

        $closures = $this->closureRepository->findAncestors($child);

        self::assertCount(2, $closures);

        $depths = array_map(static fn (ClosureInterface $c): int => $c->getDepth(), $closures);
        sort($depths);
        self::assertSame([0, 1], $depths);
    }

    /**
     * @test
     */
    public function it_creates_closures_for_grandchild_item(): void
    {
        $navigation = $this->createNavigation('create_grandchild');
        $root = $this->createItem($navigation);
        $child = $this->createItem($navigation);
        $grandchild = $this->createItem($navigation);

        $this->closureManager->createItem($root);
        $this->closureManager->createItem($child, $root);
        $this->closureManager->createItem($grandchild, $child);

        $closures = $this->closureRepository->findAncestors($grandchild);

        self::assertCount(3, $closures);

        $depths = array_map(static fn (ClosureInterface $c): int => $c->getDepth(), $closures);
        sort($depths);
        self::assertSame([0, 1, 2], $depths);
    }

    /**
     * @test
     */
    public function it_does_not_create_duplicate_self_closure(): void
    {
        $navigation = $this->createNavigation('no_dup_self');
        $item = $this->createItem($navigation);

        $this->closureManager->createItem($item);
        $this->closureManager->createItem($item);

        $closures = $this->closureRepository->findAncestors($item);

        self::assertCount(1, $closures);
    }

    /**
     * @test
     */
    public function it_does_not_create_duplicate_parent_closures(): void
    {
        $navigation = $this->createNavigation('no_dup_parent');
        $parent = $this->createItem($navigation);
        $child = $this->createItem($navigation);

        $this->closureManager->createItem($parent);
        $this->closureManager->createItem($child, $parent);
        $this->closureManager->createItem($child, $parent);

        $closures = $this->closureRepository->findAncestors($child);

        self::assertCount(2, $closures);
    }

    /**
     * @test
     */
    public function it_supports_deferred_flush(): void
    {
        $navigation = $this->createNavigation('deferred_flush');
        $item = $this->createItem($navigation);

        $this->closureManager->createItem($item, null, false);

        // Before flush, closures should not be persisted to the DB yet
        // but the entity manager should have them queued
        $this->entityManager->flush();

        $closures = $this->closureRepository->findAncestors($item);
        self::assertCount(1, $closures);
    }

    /**
     * @test
     */
    public function it_removes_tree_including_all_descendants(): void
    {
        $navigation = $this->createNavigation('remove_tree');
        $root = $this->createItem($navigation);
        $child = $this->createItem($navigation);
        $grandchild = $this->createItem($navigation);

        $this->closureManager->createItem($root);
        $this->closureManager->createItem($child, $root);
        $this->closureManager->createItem($grandchild, $child);

        $rootId = $root->getId();
        $childId = $child->getId();
        $grandchildId = $grandchild->getId();

        $this->closureManager->removeTree($root);

        // All closures should be gone
        $this->entityManager->clear();

        $itemRepository = self::getContainer()->get('setono_sylius_navigation.repository.item');

        self::assertNull($itemRepository->find($rootId));
        self::assertNull($itemRepository->find($childId));
        self::assertNull($itemRepository->find($grandchildId));
    }

    /**
     * @test
     */
    public function it_removes_single_item_tree(): void
    {
        $navigation = $this->createNavigation('remove_single');
        $item = $this->createItem($navigation);

        $this->closureManager->createItem($item);

        $itemId = $item->getId();

        $this->closureManager->removeTree($item);

        $this->entityManager->clear();

        $itemRepository = self::getContainer()->get('setono_sylius_navigation.repository.item');
        self::assertNull($itemRepository->find($itemId));
    }

    /**
     * @test
     */
    public function it_does_not_affect_sibling_trees_when_removing(): void
    {
        $navigation = $this->createNavigation('remove_sibling');
        $root1 = $this->createItem($navigation);
        $root2 = $this->createItem($navigation);
        $child1 = $this->createItem($navigation);

        $this->closureManager->createItem($root1);
        $this->closureManager->createItem($root2);
        $this->closureManager->createItem($child1, $root1);

        $root2Id = $root2->getId();

        $this->closureManager->removeTree($root1);

        $this->entityManager->clear();

        $itemRepository = self::getContainer()->get('setono_sylius_navigation.repository.item');
        self::assertNotNull($itemRepository->find($root2Id));
    }

    /**
     * @test
     */
    public function it_moves_item_to_new_parent(): void
    {
        $navigation = $this->createNavigation('move_parent');
        $parent1 = $this->createItem($navigation);
        $parent2 = $this->createItem($navigation);
        $child = $this->createItem($navigation);

        $this->closureManager->createItem($parent1);
        $this->closureManager->createItem($parent2);
        $this->closureManager->createItem($child, $parent1);

        // Verify child is under parent1
        $ancestorsBefore = $this->closureRepository->findAncestors($child);
        $ancestorIdsBefore = array_map(
            static fn (ClosureInterface $c) => $c->getAncestor()?->getId(),
            array_filter($ancestorsBefore, static fn (ClosureInterface $c) => $c->getDepth() > 0),
        );
        self::assertContains($parent1->getId(), $ancestorIdsBefore);

        // Move child to parent2
        $this->closureManager->moveItem($child, $parent2, 0);

        $this->entityManager->clear();

        $child = self::getContainer()->get('setono_sylius_navigation.repository.item')->find($child->getId());
        $ancestorsAfter = $this->closureRepository->findAncestors($child);

        $ancestorIdsAfter = array_map(
            static fn (ClosureInterface $c) => $c->getAncestor()?->getId(),
            array_filter($ancestorsAfter, static fn (ClosureInterface $c) => $c->getDepth() > 0),
        );

        self::assertNotContains($parent1->getId(), $ancestorIdsAfter);
        self::assertContains($parent2->getId(), $ancestorIdsAfter);
    }

    /**
     * @test
     */
    public function it_moves_item_to_root_level(): void
    {
        $navigation = $this->createNavigation('move_to_root');
        $parent = $this->createItem($navigation);
        $child = $this->createItem($navigation);

        $this->closureManager->createItem($parent);
        $this->closureManager->createItem($child, $parent);

        // Move child to root (no parent)
        $this->closureManager->moveItem($child, null, 0);

        $ancestorsAfter = $this->closureRepository->findAncestors($child);

        // Should only have self-reference
        self::assertCount(1, $ancestorsAfter);
        self::assertSame(0, $ancestorsAfter[0]->getDepth());
    }

    /**
     * @test
     */
    public function it_reorders_siblings_when_moving_item(): void
    {
        $navigation = $this->createNavigation('reorder_siblings');
        $root = $this->createItem($navigation);
        $childA = $this->createItem($navigation);
        $childB = $this->createItem($navigation);
        $childC = $this->createItem($navigation);

        $this->closureManager->createItem($root);
        $this->closureManager->createItem($childA, $root);
        $this->closureManager->createItem($childB, $root);
        $this->closureManager->createItem($childC, $root);

        // Move childA to position 2 (after childC)
        $this->closureManager->moveItem($childA, $root, 2);

        $this->entityManager->clear();

        $itemRepository = self::getContainer()->get('setono_sylius_navigation.repository.item');
        $updatedA = $itemRepository->find($childA->getId());
        $updatedB = $itemRepository->find($childB->getId());
        $updatedC = $itemRepository->find($childC->getId());

        self::assertSame(0, $updatedB->getPosition());
        self::assertSame(1, $updatedC->getPosition());
        self::assertSame(2, $updatedA->getPosition());
    }

    /**
     * @test
     */
    public function it_clamps_position_to_valid_range(): void
    {
        $navigation = $this->createNavigation('clamp_position');
        $root = $this->createItem($navigation);
        $childA = $this->createItem($navigation);
        $childB = $this->createItem($navigation);

        $this->closureManager->createItem($root);
        $this->closureManager->createItem($childA, $root);
        $this->closureManager->createItem($childB, $root);

        // Move childA to an excessively large position — should clamp to end
        $this->closureManager->moveItem($childA, $root, 999);

        $this->entityManager->clear();

        $itemRepository = self::getContainer()->get('setono_sylius_navigation.repository.item');
        $updatedA = $itemRepository->find($childA->getId());
        $updatedB = $itemRepository->find($childB->getId());

        self::assertSame(0, $updatedB->getPosition());
        self::assertSame(1, $updatedA->getPosition());
    }

    /**
     * @test
     */
    public function it_handles_moving_item_to_same_parent_different_position(): void
    {
        $navigation = $this->createNavigation('same_parent_reorder');
        $root = $this->createItem($navigation);
        $childA = $this->createItem($navigation);
        $childB = $this->createItem($navigation);
        $childC = $this->createItem($navigation);

        $this->closureManager->createItem($root);
        $this->closureManager->createItem($childA, $root);
        $this->closureManager->createItem($childB, $root);
        $this->closureManager->createItem($childC, $root);

        // Move childC to position 0 (first)
        $this->closureManager->moveItem($childC, $root, 0);

        $this->entityManager->clear();

        $itemRepository = self::getContainer()->get('setono_sylius_navigation.repository.item');
        $updatedA = $itemRepository->find($childA->getId());
        $updatedB = $itemRepository->find($childB->getId());
        $updatedC = $itemRepository->find($childC->getId());

        self::assertSame(0, $updatedC->getPosition());
        self::assertSame(1, $updatedA->getPosition());
        self::assertSame(2, $updatedB->getPosition());
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
}
