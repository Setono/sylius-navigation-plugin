<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusNavigationPlugin\Controller\Command\BuildFromTaxonCommand;
use Setono\SyliusNavigationPlugin\Factory\TaxonItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Form\Type\BuildFromTaxonType;
use Setono\SyliusNavigationPlugin\Manager\ClosureManagerInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

final class BuildFromTaxonController extends AbstractController
{
    use ORMTrait;

    public function __construct(
        private readonly TaxonItemFactoryInterface $taxonItemFactory,
        private readonly ClosureManagerInterface $closureManager,
        private readonly ClosureRepositoryInterface $closureRepository,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(Request $request, NavigationInterface $navigation): Response
    {
        $command = new BuildFromTaxonCommand();
        $form = $this->createForm(BuildFromTaxonType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->build($navigation, $command);

            $this->addFlash('success', 'setono_sylius_navigation.navigation_built');

            return $this->redirectToRoute('setono_sylius_navigation_admin_navigation_update', [
                'id' => $navigation->getId(),
            ]);
        }

        $hasItems = [] !== $this->closureRepository->findRootItems($navigation);

        return $this->render('@SetonoSyliusNavigationPlugin/navigation/build_from_taxon.html.twig', [
            'form' => $form->createView(),
            'navigation' => $navigation,
            'hasItems' => $hasItems,
        ]);
    }

    private function build(NavigationInterface $navigation, BuildFromTaxonCommand $command): void
    {
        Assert::isInstanceOf($command->taxon, TaxonInterface::class);

        // Remove all existing items for this navigation
        $existingItems = $this->closureRepository->findRootItems($navigation);
        foreach ($existingItems as $item) {
            $this->closureManager->removeTree($item);
        }

        // Fetch all descendants in a single query using nested set
        $taxons = $this->fetchDescendants($command->taxon, $command->includeRoot, $command->maxDepth);

        if ([] === $taxons) {
            return;
        }

        /** @var \SplObjectStorage<TaxonInterface, ItemInterface> $taxonToItemStorage */
        $taxonToItemStorage = new \SplObjectStorage();

        // Create items in order (parents before children)
        foreach ($taxons as $taxon) {
            $item = $this->createItemFromTaxon($taxon, $navigation);
            $this->getManager($item)->persist($item);
            $this->getManager($item)->flush();

            $taxonToItemStorage->attach($taxon, $item);

            // Determine parent item
            $parent = $taxon->getParent();
            $parentItem = null;

            if (null !== $parent && $taxonToItemStorage->contains($parent)) {
                $parentItem = $taxonToItemStorage[$parent];
            } elseif (!$command->includeRoot && $parent === $command->taxon) {
                // If we're not including root and the parent is the root, this should be a root item (no parent)
                $parentItem = null;
            }

            $this->closureManager->createItem($item, $parentItem);
        }

        $this->getManager($navigation)->flush();
    }

    /**
     * Fetch descendants using nested set (single query)
     *
     * @return list<TaxonInterface>
     */
    private function fetchDescendants(TaxonInterface $root, bool $includeRoot, ?int $maxDepth): array
    {
        $entityManager = $this->getManager($root);
        $qb = $entityManager->createQueryBuilder();

        $qb->select('t')
            ->from($root::class, 't');

        if ($includeRoot) {
            // Include root and all descendants
            $qb->where('t.left >= :left')
                ->andWhere('t.right <= :right')
                ->setParameter('left', $root->getLeft())
                ->setParameter('right', $root->getRight());

            if (null !== $maxDepth) {
                $qb->andWhere('t.level <= :maxLevel')
                    ->setParameter('maxLevel', $root->getLevel() + $maxDepth - 1);
            }
        } else {
            // Exclude root, only descendants
            $qb->where('t.left > :left')
                ->andWhere('t.right < :right')
                ->setParameter('left', $root->getLeft())
                ->setParameter('right', $root->getRight());

            if (null !== $maxDepth) {
                $qb->andWhere('t.level <= :maxLevel')
                    ->setParameter('maxLevel', $root->getLevel() + $maxDepth);
            }
        }

        // Order by left to ensure parents come before children
        $qb->orderBy('t.left', 'ASC');

        /** @var list<TaxonInterface> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    private function createItemFromTaxon(TaxonInterface $taxon, NavigationInterface $navigation): TaxonItemInterface
    {
        $item = $this->taxonItemFactory->createNew();
        $item->setNavigation($navigation);
        $item->setTaxon($taxon);

        return $item;
    }
}
