<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

interface LinkItemInterface extends ItemInterface
{
    public function getUrl(): ?string;

    public function setUrl(?string $url): void;

    public function isOpenInNewTab(): bool;

    public function setOpenInNewTab(bool $openInNewTab): void;

    public function isNofollow(): bool;

    public function setNofollow(bool $nofollow): void;

    public function isNoopener(): bool;

    public function setNoopener(bool $noopener): void;

    public function isNoreferrer(): bool;

    public function setNoreferrer(bool $noreferrer): void;

    /**
     * Get the computed target attribute value (_blank if open in new tab, null otherwise)
     */
    public function getTarget(): ?string;

    /**
     * Get the computed rel attribute value based on nofollow, noopener, noreferrer flags
     */
    public function getRel(): ?string;
}
