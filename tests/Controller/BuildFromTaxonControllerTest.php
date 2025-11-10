<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusNavigationPlugin\Controller\BuildFromTaxonController;
use Setono\SyliusNavigationPlugin\Controller\Command\BuildFromTaxonCommand;
use Setono\SyliusNavigationPlugin\Factory\TaxonItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Manager\ClosureManagerInterface;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItem;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class BuildFromTaxonControllerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $taxonItemFactory;

    private ObjectProphecy $closureManager;

    private ObjectProphecy $closureRepository;

    private ObjectProphecy $managerRegistry;

    private BuildFromTaxonController $controller;

    protected function setUp(): void
    {
        $this->taxonItemFactory = $this->prophesize(TaxonItemFactoryInterface::class);
        $this->closureManager = $this->prophesize(ClosureManagerInterface::class);
        $this->closureRepository = $this->prophesize(ClosureRepositoryInterface::class);
        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);

        $this->controller = new BuildFromTaxonController(
            $this->taxonItemFactory->reveal(),
            $this->closureManager->reveal(),
            $this->closureRepository->reveal(),
            $this->managerRegistry->reveal(),
        );
    }

    /**
     * @test
     */
    public function it_limits_depth_when_max_depth_is_set(): void
    {
        // Create a taxon tree with nested set values:
        // Root (left=1, right=8, level=0)
        //   -> Child1 (left=2, right=7, level=1)
        //     -> Grandchild1 (left=3, right=6, level=2)
        //       -> GreatGrandchild1 (left=4, right=5, level=3)
        $root = $this->createTaxon('root', null, 1, 8, 0);
        $child1 = $this->createTaxon('child1', $root, 2, 7, 1);
        $grandchild1 = $this->createTaxon('grandchild1', $child1, 3, 6, 2);
        $greatGrandchild1 = $this->createTaxon('great-grandchild1', $grandchild1, 4, 5, 3);

        $navigation = $this->createNavigation('test-nav');

        // Expect only 3 items to be created (root, child1, grandchild1) when maxDepth = 3
        $rootItem = $this->createTaxonItem($root, $navigation);
        $child1Item = $this->createTaxonItem($child1, $navigation);
        $grandchild1Item = $this->createTaxonItem($grandchild1, $navigation);

        $this->taxonItemFactory->createNew()->willReturn(
            $rootItem,
            $child1Item,
            $grandchild1Item,
        );

        // Verify that only 3 items are created via closureManager (without flush)
        $this->closureManager->createItem($rootItem, null, false)->shouldBeCalledOnce();
        $this->closureManager->createItem($child1Item, $rootItem, false)->shouldBeCalledOnce();
        $this->closureManager->createItem($grandchild1Item, $child1Item, false)->shouldBeCalledOnce();

        // This reflection-based approach allows us to test the private build method
        $buildMethod = new \ReflectionMethod($this->controller, 'build');
        $buildMethod->setAccessible(true);

        $this->closureRepository->findRootItems($navigation)->willReturn([]);

        // Mock the query builder and entity manager for nested set query
        $query = $this->prophesize(\Doctrine\ORM\AbstractQuery::class);
        $query->getResult()->willReturn([$root->reveal(), $child1->reveal(), $grandchild1->reveal()]);

        $qb = $this->prophesize(\Doctrine\ORM\QueryBuilder::class);
        $qb->select('t')->willReturn($qb);
        $qb->from(Argument::any(), 't')->willReturn($qb);
        $qb->where(Argument::any())->willReturn($qb);
        $qb->andWhere(Argument::any())->willReturn($qb);
        $qb->setParameter(Argument::any(), Argument::any())->willReturn($qb);
        $qb->orderBy('t.left', 'ASC')->willReturn($qb);
        $qb->getQuery()->willReturn($query->reveal());

        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->persist(Argument::any())->willReturn(null);
        $manager->flush()->willReturn(null);
        $manager->createQueryBuilder()->willReturn($qb->reveal());
        $this->managerRegistry->getManagerForClass(Argument::any())->willReturn($manager->reveal());

        // Call build with maxDepth = 3
        $command = new BuildFromTaxonCommand();
        $command->taxon = $root->reveal();
        $command->includeRoot = true;
        $command->maxDepth = 3;

        $buildMethod->invoke($this->controller, $navigation, $command);
    }

    /**
     * @test
     */
    public function it_builds_full_tree_when_max_depth_is_null(): void
    {
        // Create a taxon tree with nested set values
        $root = $this->createTaxon('root', null, 1, 8, 0);
        $child1 = $this->createTaxon('child1', $root, 2, 7, 1);
        $grandchild1 = $this->createTaxon('grandchild1', $child1, 3, 6, 2);
        $greatGrandchild1 = $this->createTaxon('great-grandchild1', $grandchild1, 4, 5, 3);

        $navigation = $this->createNavigation('test-nav');

        // Expect all 4 items to be created when maxDepth = null
        $rootItem = $this->createTaxonItem($root, $navigation);
        $child1Item = $this->createTaxonItem($child1, $navigation);
        $grandchild1Item = $this->createTaxonItem($grandchild1, $navigation);
        $greatGrandchild1Item = $this->createTaxonItem($greatGrandchild1, $navigation);

        $this->taxonItemFactory->createNew()->willReturn(
            $rootItem,
            $child1Item,
            $grandchild1Item,
            $greatGrandchild1Item,
        );

        // Verify that all 4 items are created (without flush)
        $this->closureManager->createItem($rootItem, null, false)->shouldBeCalledOnce();
        $this->closureManager->createItem($child1Item, $rootItem, false)->shouldBeCalledOnce();
        $this->closureManager->createItem($grandchild1Item, $child1Item, false)->shouldBeCalledOnce();
        $this->closureManager->createItem($greatGrandchild1Item, $grandchild1Item, false)->shouldBeCalledOnce();

        $buildMethod = new \ReflectionMethod($this->controller, 'build');
        $buildMethod->setAccessible(true);

        $this->closureRepository->findRootItems($navigation)->willReturn([]);

        // Mock the query builder for nested set query
        $query = $this->prophesize(\Doctrine\ORM\AbstractQuery::class);
        $query->getResult()->willReturn([$root->reveal(), $child1->reveal(), $grandchild1->reveal(), $greatGrandchild1->reveal()]);

        $qb = $this->prophesize(\Doctrine\ORM\QueryBuilder::class);
        $qb->select('t')->willReturn($qb);
        $qb->from(Argument::any(), 't')->willReturn($qb);
        $qb->where(Argument::any())->willReturn($qb);
        $qb->andWhere(Argument::any())->willReturn($qb);
        $qb->setParameter(Argument::any(), Argument::any())->willReturn($qb);
        $qb->orderBy('t.left', 'ASC')->willReturn($qb);
        $qb->getQuery()->willReturn($query->reveal());

        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->persist(Argument::any())->willReturn(null);
        $manager->flush()->willReturn(null);
        $manager->createQueryBuilder()->willReturn($qb->reveal());
        $this->managerRegistry->getManagerForClass(Argument::any())->willReturn($manager->reveal());

        // Call build with maxDepth = null (unlimited)
        $command = new BuildFromTaxonCommand();
        $command->taxon = $root->reveal();
        $command->includeRoot = true;
        $command->maxDepth = null;

        $buildMethod->invoke($this->controller, $navigation, $command);
    }

    /**
     * @test
     */
    public function it_respects_max_depth_when_not_including_root(): void
    {
        // When not including root, children of root become level 1
        $root = $this->createTaxon('root', null, 1, 8, 0);
        $child1 = $this->createTaxon('child1', $root, 2, 7, 1);
        $grandchild1 = $this->createTaxon('grandchild1', $child1, 3, 6, 2);
        $greatGrandchild1 = $this->createTaxon('great-grandchild1', $grandchild1, 4, 5, 3);

        $navigation = $this->createNavigation('test-nav');

        // With maxDepth=2 and includeRoot=false:
        // Level 1: child1
        // Level 2: grandchild1
        // greatGrandchild1 should NOT be created
        $child1Item = $this->createTaxonItem($child1, $navigation);
        $grandchild1Item = $this->createTaxonItem($grandchild1, $navigation);

        $this->taxonItemFactory->createNew()->willReturn(
            $child1Item,
            $grandchild1Item,
        );

        // Verify only 2 items created (without flush)
        $this->closureManager->createItem($child1Item, null, false)->shouldBeCalledOnce();
        $this->closureManager->createItem($grandchild1Item, $child1Item, false)->shouldBeCalledOnce();

        $buildMethod = new \ReflectionMethod($this->controller, 'build');
        $buildMethod->setAccessible(true);

        $this->closureRepository->findRootItems($navigation)->willReturn([]);

        // Mock the query builder for nested set query
        $query = $this->prophesize(\Doctrine\ORM\AbstractQuery::class);
        $query->getResult()->willReturn([$child1->reveal(), $grandchild1->reveal()]);

        $qb = $this->prophesize(\Doctrine\ORM\QueryBuilder::class);
        $qb->select('t')->willReturn($qb);
        $qb->from(Argument::any(), 't')->willReturn($qb);
        $qb->where(Argument::any())->willReturn($qb);
        $qb->andWhere(Argument::any())->willReturn($qb);
        $qb->setParameter(Argument::any(), Argument::any())->willReturn($qb);
        $qb->orderBy('t.left', 'ASC')->willReturn($qb);
        $qb->getQuery()->willReturn($query->reveal());

        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->persist(Argument::any())->willReturn(null);
        $manager->flush()->willReturn(null);
        $manager->createQueryBuilder()->willReturn($qb->reveal());
        $this->managerRegistry->getManagerForClass(Argument::any())->willReturn($manager->reveal());

        // Call build with maxDepth = 2 and includeRoot = false
        $command = new BuildFromTaxonCommand();
        $command->taxon = $root->reveal();
        $command->includeRoot = false;
        $command->maxDepth = 2;

        $buildMethod->invoke($this->controller, $navigation, $command);
    }

    /**
     * @param ObjectProphecy|null $parent
     */
    private function createTaxon(string $code, $parent, int $left = 1, int $right = 2, int $level = 0): ObjectProphecy
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getCode()->willReturn($code);
        $taxon->getParent()->willReturn($parent ? $parent->reveal() : null);
        $taxon->getLeft()->willReturn($left);
        $taxon->getRight()->willReturn($right);
        $taxon->getLevel()->willReturn($level);

        return $taxon;
    }

    private function createNavigation(string $code): NavigationInterface
    {
        $navigation = new Navigation();
        $reflection = new \ReflectionClass($navigation);
        $property = $reflection->getProperty('code');
        $property->setValue($navigation, $code);

        return $navigation;
    }

    private function createTaxonItem(ObjectProphecy $taxon, NavigationInterface $navigation): TaxonItemInterface
    {
        $taxonItem = new TaxonItem();
        $taxonItem->setCurrentLocale('en_US');
        $taxonItem->setFallbackLocale('en_US');
        $taxonItem->setTaxon($taxon->reveal());
        $taxonItem->setNavigation($navigation);

        return $taxonItem;
    }
}
