<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Graph;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;

interface GraphBuilderInterface
{
    /**
     * Builds a graph of navigation items
     *
     * @return iterable<Node>
     */
    public function build(NavigationInterface $navigation): iterable;
}
