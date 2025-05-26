<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusNavigationPlugin\Factory\TaxonItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Form\Type\BuildFromTaxonType;
use Setono\SyliusNavigationPlugin\Manager\ClosureManagerInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
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

        $form = $this->createForm(BuildFromTaxonType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            Assert::isArray($data);

            /** @var TaxonInterface|null $taxon */
            $taxon = $data['taxon'] ?? null;
            Assert::isInstanceOf($taxon, TaxonInterface::class);

            $this->build($navigation, $taxon);

            $this->addFlash('success', 'setono_sylius_navigation.navigation_built');

            return $this->redirectToRoute('setono_sylius_navigation_admin_navigation_update', [
                'id' => $navigation->getId(),
            ]);
        }

        return $this->render('@SetonoSyliusNavigationPlugin/navigation/build_from_taxon.html.twig', [
            'form' => $form->createView(),
            'navigation' => $navigation,
        ]);
    }

    private function build(NavigationInterface $navigation, TaxonInterface $root): void
    {
        $rootItem = $navigation->getRootItem();
        if (null !== $rootItem) {
            $navigation->setRootItem(null);
            $this->closureManager->removeTree($rootItem);
        }

        /** @var \SplObjectStorage<TaxonInterface, ItemInterface> $taxonToItemStorage */
        $taxonToItemStorage = new \SplObjectStorage();

        /** @var list<TaxonInterface> $taxons */
        $taxons = [$root];

        while ([] !== $taxons) {
            $taxon = array_shift($taxons);
            $parent = $taxon->getParent();

            $item = $this->createItemFromTaxon($taxon);
            $this->getManager($item)->persist($item);
            $this->getManager($item)->flush();

            $taxonToItemStorage->attach($taxon, $item);

            $this->closureManager->createItem($item, null !== $parent && $taxonToItemStorage->contains($parent) ? $taxonToItemStorage[$parent] : null);

            foreach ($taxon->getChildren() as $child) {
                $taxons[] = $child;
            }
        }

        $navigation->setRootItem($taxonToItemStorage[$root]);

        $this->getManager($navigation)->flush();
    }

    private function createItemFromTaxon(TaxonInterface $taxon): TaxonItemInterface
    {
        $item = $this->taxonItemFactory->createNew();

        // todo should be set for each locale
        $item->setLabel($taxon->getName());
        $item->setTaxon($taxon);

        return $item;
    }
}
