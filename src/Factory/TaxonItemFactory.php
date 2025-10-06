<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\TaxonItemInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class TaxonItemFactory implements TaxonItemFactoryInterface
{
    /**
     * @param FactoryInterface<TaxonItemInterface> $decoratedFactory
     */
    public function __construct(private readonly FactoryInterface $decoratedFactory)
    {
    }

    public function createNew(): TaxonItemInterface
    {
        $obj = $this->decoratedFactory->createNew();
        Assert::isInstanceOf($obj, TaxonItemInterface::class);

        return $obj;
    }
}
