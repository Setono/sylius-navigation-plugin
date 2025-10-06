<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\ClosureInterface;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

/**
 * @extends FactoryInterface<ClosureInterface>
 */
interface ClosureFactoryInterface extends FactoryInterface
{
    public function createNew(): ClosureInterface;

    public function createSelfRelationship(ItemInterface $ancestor): ClosureInterface;

    public function createRelationship(ItemInterface $ancestor, ItemInterface $descendant, int $depth): ClosureInterface;
}
