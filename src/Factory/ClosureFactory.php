<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\ClosureInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class ClosureFactory implements ClosureFactoryInterface
{
    /**
     * @param FactoryInterface<ClosureInterface> $decorated
     */
    public function __construct(private readonly FactoryInterface $decorated)
    {
    }

    public function createNew(): ClosureInterface
    {
        $obj = $this->decorated->createNew();
        Assert::isInstanceOf($obj, ClosureInterface::class);

        return $obj;
    }

    public function createSelfRelationship(ItemInterface $ancestor): ClosureInterface
    {
        return $this->createRelationship($ancestor, $ancestor, 0);
    }

    public function createRelationship(ItemInterface $ancestor, ItemInterface $descendant, int $depth): ClosureInterface
    {
        $obj = $this->createNew();
        $obj->setAncestor($ancestor);
        $obj->setDescendant($descendant);
        $obj->setDepth($depth);

        return $obj;
    }
}
