<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Sylius\Component\Resource\Model\AbstractTranslation;

class ItemTranslation extends AbstractTranslation implements ItemTranslationInterface
{
    protected ?int $id = null;

    protected ?string $label = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }
}
