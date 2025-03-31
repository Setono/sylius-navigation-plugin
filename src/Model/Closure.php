<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

class Closure implements ClosureInterface
{
    protected ?int $id = null;

    protected ?ItemInterface $ancestor = null;

    protected ?ItemInterface $descendant = null;

    protected ?int $depth = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAncestor(): ?ItemInterface
    {
        return $this->ancestor;
    }

    public function setAncestor(?ItemInterface $ancestor): void
    {
        $this->ancestor = $ancestor;
    }

    public function getDescendant(): ?ItemInterface
    {
        return $this->descendant;
    }

    public function setDescendant(?ItemInterface $descendant): void
    {
        $this->descendant = $descendant;
    }

    public function getDepth(): ?int
    {
        return $this->depth;
    }

    public function setDepth(?int $depth): void
    {
        $this->depth = $depth;
    }
}
