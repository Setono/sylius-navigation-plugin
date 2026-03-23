<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Functional\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Repository\TaxonItemRepositoryInterface;
use Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TaxonItemRepositoryTest extends KernelTestCase
{
    private TaxonItemRepositoryInterface $repository;

    private EntityManagerInterface $entityManager;

    private TaxonFactoryInterface $taxonFactory;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $this->repository = $container->get('setono_sylius_navigation.repository.taxon_item');
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->taxonFactory = $container->get('sylius.factory.taxon');
    }

    /**
     * @test
     */
    public function it_finds_taxon_items_by_taxon(): void
    {
        $taxon = $this->createTaxon('electronics');
        $navigation = $this->createNavigation('taxon_find');

        $item1 = $this->createTaxonItem($navigation, $taxon);
        $item2 = $this->createTaxonItem($navigation, $taxon);

        $this->entityManager->flush();

        $results = $this->repository->findByTaxon($taxon);

        self::assertCount(2, $results);

        $resultIds = array_map(static fn (TaxonItemInterface $i): ?int => $i->getId(), $results);
        self::assertContains($item1->getId(), $resultIds);
        self::assertContains($item2->getId(), $resultIds);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_no_items_reference_taxon(): void
    {
        $taxon = $this->createTaxon('orphan_taxon');

        $this->entityManager->flush();

        $results = $this->repository->findByTaxon($taxon);

        self::assertSame([], $results);
    }

    /**
     * @test
     */
    public function it_does_not_return_items_for_different_taxon(): void
    {
        $taxon1 = $this->createTaxon('clothing');
        $taxon2 = $this->createTaxon('books');
        $navigation = $this->createNavigation('taxon_diff');

        $this->createTaxonItem($navigation, $taxon1);

        $this->entityManager->flush();

        $results = $this->repository->findByTaxon($taxon2);

        self::assertSame([], $results);
    }

    /**
     * @test
     */
    public function it_finds_items_across_multiple_navigations(): void
    {
        $taxon = $this->createTaxon('shared_taxon');
        $nav1 = $this->createNavigation('nav_a');
        $nav2 = $this->createNavigation('nav_b');

        $item1 = $this->createTaxonItem($nav1, $taxon);
        $item2 = $this->createTaxonItem($nav2, $taxon);

        $this->entityManager->flush();

        $results = $this->repository->findByTaxon($taxon);

        self::assertCount(2, $results);

        $resultIds = array_map(static fn (TaxonItemInterface $i): ?int => $i->getId(), $results);
        self::assertContains($item1->getId(), $resultIds);
        self::assertContains($item2->getId(), $resultIds);
    }

    private function createTaxon(string $code): TaxonInterface
    {
        /** @var TaxonInterface $taxon */
        $taxon = $this->taxonFactory->createNew();
        $taxon->setCode($code);
        $taxon->setCurrentLocale('en_US');
        $taxon->setFallbackLocale('en_US');
        $taxon->setName(ucfirst($code));
        $taxon->setSlug($code);

        $this->entityManager->persist($taxon);
        $this->entityManager->flush();

        return $taxon;
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

    private function createTaxonItem(NavigationInterface $navigation, TaxonInterface $taxon): TaxonItemInterface
    {
        $factory = self::getContainer()->get('setono_sylius_navigation.factory.taxon_item');
        /** @var TaxonItemInterface $item */
        $item = $factory->createNew();
        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setNavigation($navigation);
        $item->setTaxon($taxon);

        $this->entityManager->persist($item);

        return $item;
    }
}
