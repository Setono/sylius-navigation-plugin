<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\ArgumentResolver;

use Doctrine\Persistence\ObjectRepository;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ItemValueResolver implements ValueResolverInterface
{
    /**
     * @param ObjectRepository<ItemInterface> $itemRepository
     */
    public function __construct(
        private readonly ObjectRepository $itemRepository,
    ) {
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

        // Find the item entity using the item repository
        $item = $this->itemRepository->find((int) $itemId);

        if (!$item instanceof ItemInterface) {
            throw new NotFoundHttpException(sprintf('Item with id "%d" not found', (int) $itemId));
        }

        return [$item];
    }
}
