<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslationInterface;

interface ItemTranslationInterface extends ResourceInterface, TranslationInterface
{
    public function getLabel(): ?string;

    public function setLabel(?string $label): void;
}
