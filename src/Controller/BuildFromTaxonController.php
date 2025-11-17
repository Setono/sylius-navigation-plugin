<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusNavigationPlugin\Controller\Command\BuildFromTaxonCommand;
use Setono\SyliusNavigationPlugin\Form\Type\BuildFromTaxonType;
use Setono\SyliusNavigationPlugin\Message\Command\BuildNavigationFromTaxon;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\ClosureRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final class BuildFromTaxonController extends AbstractController
{
    use ORMTrait;

    public function __construct(
        private readonly ClosureRepositoryInterface $closureRepository,
        private readonly MessageBusInterface $messageBus,
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
            $navigation->setState(NavigationInterface::STATE_BUILDING);
            $manager = $this->getManager($navigation);
            $manager->flush();

            Assert::notNull($command->taxon);

            $this->messageBus->dispatch(new BuildNavigationFromTaxon(
                navigation: $navigation,
                taxon: $command->taxon,
                includeRoot: $command->includeRoot,
                maxDepth: $command->maxDepth,
            ));

            $this->addFlash('success', 'setono_sylius_navigation.build_queued');

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
}
