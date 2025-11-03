<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\LinkItemInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class LinkItemFactory implements LinkItemFactoryInterface
{
    /**
     * @param FactoryInterface<LinkItemInterface> $decoratedFactory
     */
    public function __construct(private readonly FactoryInterface $decoratedFactory)
    {
    }

    public function createNew(): LinkItemInterface
    {
        $obj = $this->decoratedFactory->createNew();
        Assert::isInstanceOf($obj, LinkItemInterface::class);

        return $obj;
    }
}
