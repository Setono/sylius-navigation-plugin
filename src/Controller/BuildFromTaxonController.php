<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Controller;

use Setono\SyliusNavigationPlugin\Form\Type\BuildFromTaxonType;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

final class BuildFromTaxonController extends AbstractController
{
    public function __construct(private readonly NavigationRepositoryInterface $navigationRepository)
    {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $navigation = $this->navigationRepository->find($id);
        if (!$navigation instanceof NavigationInterface) {
            $this->addFlash('error', 'setono_sylius_navigation.navigation_not_found');

            return $this->redirectToRoute('setono_sylius_navigation_admin_navigation_index');
        }

        if ($navigation->getRootItem() !== null) {
            $this->addFlash('error', 'setono_sylius_navigation.navigation_already_built');

            return $this->redirectToRoute('setono_sylius_navigation_admin_navigation_update', [
                'id' => $navigation->getId(),
            ]);
        }

        $form = $this->createForm(BuildFromTaxonType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            Assert::isArray($data);

            /** @var TaxonInterface|null $taxon */
            $taxon = $data['taxon'] ?? null;
            Assert::isInstanceOf($taxon, TaxonInterface::class);
        }

        return $this->render('@SetonoSyliusNavigationPlugin/navigation/build_from_taxon.html.twig', [
            'form' => $form->createView(),
            'navigation' => $navigation,
        ]);
    }
}
