<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Functional\Builder;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusNavigationPlugin\Builder\NavigationBuilderInterface;
use Setono\SyliusNavigationPlugin\Model\ClosureInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class NavigationBuilderTest extends KernelTestCase
{
    private NavigationBuilderInterface $builder;

    private ClosureRepositoryInterface $closureRepository;

    private EntityManagerInterface $entityManager;

    private TaxonFactoryInterface $taxonFactory;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $this->builder = $container->get(NavigationBuilderInterface::class);
        $this->closureRepository = $container->get('setono_sylius_navigation.repository.closure');
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->taxonFactory = $container->get('sylius.factory.taxon');
    }

    /**
     * @test
     */
    public function it_builds_navigation_from_taxon_including_root(): void
    {
        $root = $this->createTaxonTree();
        $navigation = $this->createNavigation('including_root');

        $this->builder->buildFromTaxon($navigation, $root, true, null);

        self::assertSame(NavigationInterface::STATE_COMPLETED, $navigation->getState());

        $closures = $this->closureRepository->findByNavigation($navigation);

        // Collect items from self-referencing closures (depth = 0)
        $items = $this->extractItems($closures);

        // Root + Child A + Child B + Grandchild A1 + Grandchild A2 = 5 items
        self::assertCount(5, $items);

        // All items should be TaxonItems
        foreach ($items as $item) {
            self::assertInstanceOf(TaxonItemInterface::class, $item);
        }
    }

    /**
     * @test
     */
    public function it_builds_navigation_from_taxon_excluding_root(): void
    {
        $root = $this->createTaxonTree();
        $navigation = $this->createNavigation('excluding_root');

        $this->builder->buildFromTaxon($navigation, $root, false, null);

        self::assertSame(NavigationInterface::STATE_COMPLETED, $navigation->getState());

        $closures = $this->closureRepository->findByNavigation($navigation);
        $items = $this->extractItems($closures);

        // Child A + Child B + Grandchild A1 + Grandchild A2 = 4 items (no root)
        self::assertCount(4, $items);

        // Root-level items (no parent) should be Child A and Child B
        $rootItems = $this->closureRepository->findRootItems($navigation);
        self::assertCount(2, $rootItems);
    }

    /**
     * @test
     */
    public function it_preserves_taxon_positions_on_items(): void
    {
        $root = $this->createTaxonTree();
        $navigation = $this->createNavigation('positions');

        // Refresh taxons to get the actual positions after Gedmo processing
        $this->entityManager->refresh($root);

        $this->builder->buildFromTaxon($navigation, $root, false, null);

        $closures = $this->closureRepository->findByNavigation($navigation);
        $items = $this->extractItems($closures);

        // Verify each item's position matches its source taxon's position
        foreach ($items as $item) {
            self::assertInstanceOf(TaxonItemInterface::class, $item);
            $taxon = $item->getTaxon();
            self::assertNotNull($taxon);

            self::assertSame(
                $taxon->getPosition(),
                $item->getPosition(),
                sprintf('Item position should match taxon "%s" position', $taxon->getCode()),
            );
        }

        // Verify items have distinct positions within their sibling groups
        $rootItems = $this->closureRepository->findRootItems($navigation);
        $rootPositions = array_map(static fn ($item) => $item->getPosition(), $rootItems);
        self::assertCount(count(array_unique($rootPositions)), $rootPositions, 'Root items should have distinct positions');
    }

    /**
     * @test
     */
    public function it_respects_max_depth(): void
    {
        $root = $this->createTaxonTree();
        $navigation = $this->createNavigation('max_depth');

        // maxDepth = 1 means only direct children (excluding root)
        $this->builder->buildFromTaxon($navigation, $root, false, 1);

        self::assertSame(NavigationInterface::STATE_COMPLETED, $navigation->getState());

        $closures = $this->closureRepository->findByNavigation($navigation);
        $items = $this->extractItems($closures);

        // Only Child A and Child B (no grandchildren)
        self::assertCount(2, $items);

        foreach ($items as $item) {
            self::assertInstanceOf(TaxonItemInterface::class, $item);
            $taxon = $item->getTaxon();
            self::assertNotNull($taxon);
            self::assertContains($taxon->getCode(), ['child-a', 'child-b']);
        }
    }

    /**
     * @test
     */
    public function it_creates_correct_closure_relationships(): void
    {
        $root = $this->createTaxonTree();
        $navigation = $this->createNavigation('closures');

        $this->builder->buildFromTaxon($navigation, $root, true, null);

        $closures = $this->closureRepository->findByNavigation($navigation);
        $items = $this->extractItems($closures);

        // Build a code-to-item map for lookup
        $itemByCode = [];
        foreach ($items as $item) {
            self::assertInstanceOf(TaxonItemInterface::class, $item);
            $taxon = $item->getTaxon();
            self::assertNotNull($taxon);
            $itemByCode[$taxon->getCode()] = $item;
        }

        // Verify self-references (depth=0) exist for all items
        $selfRefs = array_filter($closures, static fn (ClosureInterface $c): bool => $c->getDepth() === 0);
        self::assertCount(5, $selfRefs);

        // Verify parent-child closures (depth=1)
        $depth1 = array_filter($closures, static fn (ClosureInterface $c): bool => $c->getDepth() === 1);

        $depth1Pairs = [];
        foreach ($depth1 as $closure) {
            $ancestor = $closure->getAncestor();
            $descendant = $closure->getDescendant();
            self::assertNotNull($ancestor);
            self::assertNotNull($descendant);
            $depth1Pairs[] = [$ancestor->getId(), $descendant->getId()];
        }

        // Expected depth-1 relationships:
        // Root -> Child A, Root -> Child B, Child A -> Grandchild A1, Child A -> Grandchild A2
        self::assertCount(4, $depth1Pairs);

        // Verify Root -> Child A closure exists
        self::assertContains(
            [$itemByCode['root']->getId(), $itemByCode['child-a']->getId()],
            $depth1Pairs,
        );

        // Verify Root -> Child B closure exists
        self::assertContains(
            [$itemByCode['root']->getId(), $itemByCode['child-b']->getId()],
            $depth1Pairs,
        );

        // Verify Child A -> Grandchild A1 closure exists
        self::assertContains(
            [$itemByCode['child-a']->getId(), $itemByCode['grandchild-a1']->getId()],
            $depth1Pairs,
        );

        // Verify Child A -> Grandchild A2 closure exists
        self::assertContains(
            [$itemByCode['child-a']->getId(), $itemByCode['grandchild-a2']->getId()],
            $depth1Pairs,
        );

        // Verify grandparent-grandchild closures (depth=2)
        $depth2 = array_filter($closures, static fn (ClosureInterface $c): bool => $c->getDepth() === 2);

        // Root -> Grandchild A1, Root -> Grandchild A2
        self::assertCount(2, $depth2);
    }

    /**
     * @test
     */
    public function it_replaces_existing_items_when_rebuilding(): void
    {
        $root = $this->createTaxonTree();
        $navigation = $this->createNavigation('rebuild');

        // Build the first time
        $this->builder->buildFromTaxon($navigation, $root, true, null);

        $firstClosures = $this->closureRepository->findByNavigation($navigation);
        $firstItems = $this->extractItems($firstClosures);
        $firstItemIds = array_map(static fn (TaxonItemInterface $item): ?int => $item->getId(), $firstItems);
        self::assertCount(5, $firstItems);

        // Build again
        $this->builder->buildFromTaxon($navigation, $root, true, null);

        $secondClosures = $this->closureRepository->findByNavigation($navigation);
        $secondItems = $this->extractItems($secondClosures);
        $secondItemIds = array_map(static fn (TaxonItemInterface $item): ?int => $item->getId(), $secondItems);

        // Same number of items
        self::assertCount(5, $secondItems);

        // But different IDs (old items were removed and new ones created)
        self::assertEmpty(
            array_intersect($firstItemIds, $secondItemIds),
            'Rebuilding should create new items, not reuse old ones',
        );

        self::assertSame(NavigationInterface::STATE_COMPLETED, $navigation->getState());
    }

    /**
     * Creates a taxon tree:
     *
     * Root (position 0)
     * ├── Child A (position 2)
     * │   ├── Grandchild A1 (position 0)
     * │   └── Grandchild A2 (position 1)
     * └── Child B (position 0)
     */
    private function createTaxonTree(): TaxonInterface
    {
        /** @var TaxonInterface $root */
        $root = $this->taxonFactory->createNew();
        $root->setCode('root');
        $root->setCurrentLocale('en_US');
        $root->setFallbackLocale('en_US');
        $root->setName('Root');
        $root->setSlug('root');
        $root->setPosition(0);

        $this->entityManager->persist($root);
        $this->entityManager->flush();

        /** @var TaxonInterface $childA */
        $childA = $this->taxonFactory->createForParent($root);
        $childA->setCode('child-a');
        $childA->setCurrentLocale('en_US');
        $childA->setFallbackLocale('en_US');
        $childA->setName('Child A');
        $childA->setSlug('child-a');
        $childA->setPosition(2);

        $this->entityManager->persist($childA);
        $this->entityManager->flush();

        /** @var TaxonInterface $childB */
        $childB = $this->taxonFactory->createForParent($root);
        $childB->setCode('child-b');
        $childB->setCurrentLocale('en_US');
        $childB->setFallbackLocale('en_US');
        $childB->setName('Child B');
        $childB->setSlug('child-b');
        $childB->setPosition(0);

        $this->entityManager->persist($childB);
        $this->entityManager->flush();

        /** @var TaxonInterface $grandchildA1 */
        $grandchildA1 = $this->taxonFactory->createForParent($childA);
        $grandchildA1->setCode('grandchild-a1');
        $grandchildA1->setCurrentLocale('en_US');
        $grandchildA1->setFallbackLocale('en_US');
        $grandchildA1->setName('Grandchild A1');
        $grandchildA1->setSlug('grandchild-a1');
        $grandchildA1->setPosition(0);

        $this->entityManager->persist($grandchildA1);
        $this->entityManager->flush();

        /** @var TaxonInterface $grandchildA2 */
        $grandchildA2 = $this->taxonFactory->createForParent($childA);
        $grandchildA2->setCode('grandchild-a2');
        $grandchildA2->setCurrentLocale('en_US');
        $grandchildA2->setFallbackLocale('en_US');
        $grandchildA2->setName('Grandchild A2');
        $grandchildA2->setSlug('grandchild-a2');
        $grandchildA2->setPosition(1);

        $this->entityManager->persist($grandchildA2);
        $this->entityManager->flush();

        return $root;
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

    /**
     * Extracts unique items from closures (using depth=0 self-references)
     *
     * @param list<ClosureInterface> $closures
     *
     * @return list<TaxonItemInterface>
     */
    private function extractItems(array $closures): array
    {
        $items = [];
        $seenIds = [];

        foreach ($closures as $closure) {
            if ($closure->getDepth() !== 0) {
                continue;
            }

            $descendant = $closure->getDescendant();
            self::assertNotNull($descendant);
            self::assertInstanceOf(TaxonItemInterface::class, $descendant);

            $id = $descendant->getId();
            if (in_array($id, $seenIds, true)) {
                continue;
            }

            $seenIds[] = $id;
            $items[] = $descendant;
        }

        return $items;
    }
}
