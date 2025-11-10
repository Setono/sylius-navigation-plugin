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
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

final class BuildFromTaxonController extends AbstractController
{
    use ORMTrait;

    public function __construct(
        private readonly NavigationRepositoryInterface $navigationRepository,
        private readonly TaxonItemFactoryInterface $taxonItemFactory,
        private readonly ClosureManagerInterface $closureManager,
        private readonly ClosureRepositoryInterface $closureRepository,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(Request $request, int $id): Response
    {
        $navigation = $this->navigationRepository->find($id);
        if (!$navigation instanceof NavigationInterface) {
            $this->addFlash('error', 'setono_sylius_navigation.navigation_not_found');

            return $this->redirectToRoute('setono_sylius_navigation_admin_navigation_index');
        }

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

        /** @var \SplObjectStorage<TaxonInterface, ItemInterface> $taxonToItemStorage */
        $taxonToItemStorage = new \SplObjectStorage();

        /** @var \SplObjectStorage<TaxonInterface, int> $taxonDepthStorage */
        $taxonDepthStorage = new \SplObjectStorage();

        /** @var list<TaxonInterface> $taxons */
        $taxons = $command->includeRoot ? [$command->taxon] : iterator_to_array($command->taxon->getChildren());

        // Initialize depth for root taxons
        foreach ($taxons as $taxon) {
            $taxonDepthStorage->attach($taxon, $command->includeRoot ? 1 : 1);
        }

        while ([] !== $taxons) {
            $taxon = array_shift($taxons);
            $parent = $taxon->getParent();

            // Get current depth
            $currentDepth = $taxonDepthStorage->contains($taxon) ? $taxonDepthStorage[$taxon] : 1;

            $item = $this->createItemFromTaxon($taxon, $navigation);
            $this->getManager($item)->persist($item);
            $this->getManager($item)->flush();

            $taxonToItemStorage->attach($taxon, $item);

            // Create closure relationships - parent is either the parent taxon's item or null (for root items)
            // When not including root, children of root should have no parent (become root items themselves)
            $parentItem = null;
            if (null !== $parent && $taxonToItemStorage->contains($parent)) {
                $parentItem = $taxonToItemStorage[$parent];
            } elseif (!$command->includeRoot && $parent === $command->taxon) {
                // If we're not including root and the parent is the root, this should be a root item (no parent)
                $parentItem = null;
            }

            $this->closureManager->createItem($item, $parentItem);

            // Only add children if we haven't reached max depth
            if (null === $command->maxDepth || $currentDepth < $command->maxDepth) {
                foreach ($taxon->getChildren() as $child) {
                    $taxons[] = $child;
                    $taxonDepthStorage->attach($child, $currentDepth + 1);
                }
            }
        }

        $this->getManager($navigation)->flush();
    }

    private function createItemFromTaxon(TaxonInterface $taxon, NavigationInterface $navigation): TaxonItemInterface
    {
        $item = $this->taxonItemFactory->createNew();
        $item->setNavigation($navigation);
        $item->setTaxon($taxon);

        return $item;
    }
}
