<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class ItemFactory implements ItemFactoryInterface
{
    public function __construct(private readonly FactoryInterface $decoratedFactory)
    {
    }

    public function createNew(): ItemInterface
    {
        $obj = $this->decoratedFactory->createNew();
        Assert::isInstanceOf($obj, ItemInterface::class);

        return $obj;
    }
}
