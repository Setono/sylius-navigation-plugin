<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\ArgumentResolver;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ItemValueResolver implements ValueResolverInterface
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Check if the argument type is ItemInterface
        if ($argument->getType() !== ItemInterface::class) {
            return [];
        }

        // Get the item parameter from the route
        $itemId = $request->attributes->get($argument->getName());

        if ($itemId === null) {
            return [];
        }

        if (!is_numeric($itemId)) {
            throw new NotFoundHttpException('Item id is not a valid numeric identifier');
        }

        // We need a navigation to get the correct manager
        // First, try to get navigation from the request (it should be resolved before item)
        $navigation = $request->attributes->get('navigation');

        if (!$navigation instanceof NavigationInterface) {
            throw new NotFoundHttpException('Navigation must be resolved before Item');
        }

        // Find the item entity using the navigation's manager
        $itemManager = $this->getManager($navigation);
        $item = $itemManager->getRepository(ItemInterface::class)->find((int) $itemId);

        if (!$item instanceof ItemInterface) {
            throw new NotFoundHttpException(sprintf('Item with id "%d" not found', (int) $itemId));
        }

        return [$item];
    }
}
