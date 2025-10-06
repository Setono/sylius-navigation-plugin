<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Factory;

use Setono\SyliusNavigationPlugin\Model\TextItemInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class TextItemFactory implements TextItemFactoryInterface
{
    /**
     * @param FactoryInterface<TextItemInterface> $decoratedFactory
     */
    public function __construct(private readonly FactoryInterface $decoratedFactory)
    {
    }

    public function createNew(): TextItemInterface
    {
        $obj = $this->decoratedFactory->createNew();
        Assert::isInstanceOf($obj, TextItemInterface::class);

        return $obj;
    }
}
