<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\ArgumentResolver;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class NavigationValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly NavigationRepositoryInterface $navigationRepository,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Check if the argument type is NavigationInterface
        if ($argument->getType() !== NavigationInterface::class) {
            return [];
        }

        // Get the navigation parameter from the route
        $navigationId = $request->attributes->get($argument->getName());

        if ($navigationId === null) {
            return [];
        }

        if (!is_numeric($navigationId)) {
            throw new NotFoundHttpException('Navigation id is not a valid numeric identifier');
        }

        // Find the navigation entity
        $navigation = $this->navigationRepository->find((int) $navigationId);

        if (!$navigation instanceof NavigationInterface) {
            throw new NotFoundHttpException(sprintf('Navigation with id "%d" not found', (int) $navigationId));
        }

        return [$navigation];
    }
}
