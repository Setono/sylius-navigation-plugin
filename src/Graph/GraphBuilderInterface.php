<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Graph;

use Setono\SyliusNavigationPlugin\Model\NavigationInterface;

interface GraphBuilderInterface
{
    public function build(NavigationInterface $navigation): Node;
}
