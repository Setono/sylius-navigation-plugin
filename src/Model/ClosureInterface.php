<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface ClosureInterface extends ResourceInterface
{
    public function getAncestor(): ?ItemInterface;

    public function setAncestor(?ItemInterface $ancestor): void;

    public function getDescendant(): ?ItemInterface;

    public function setDescendant(?ItemInterface $descendant): void;

    public function getDepth(): int;

    public function setDepth(int $depth): void;
}
