<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Setono\SyliusNavigationPlugin\Attribute\ItemType;
use Setono\SyliusNavigationPlugin\Form\Type\LinkItemType as LinkItemFormType;

#[ItemType(
    name: 'link',
    formType: LinkItemFormType::class,
    template: '@SetonoSyliusNavigationPlugin/navigation/build/form/_link_item.html.twig',
    label: 'Link Item',
    options: ['icon' => 'linkify icon'],
)]
class LinkItem extends Item implements LinkItemInterface
{
    protected ?string $url = null;

    protected bool $openInNewTab = false;

    protected bool $nofollow = false;

    protected bool $noopener = false;

    protected bool $noreferrer = false;

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function isOpenInNewTab(): bool
    {
        return $this->openInNewTab;
    }

    public function setOpenInNewTab(bool $openInNewTab): void
    {
        $this->openInNewTab = $openInNewTab;
    }

    public function isNofollow(): bool
    {
        return $this->nofollow;
    }

    public function setNofollow(bool $nofollow): void
    {
        $this->nofollow = $nofollow;
    }

    public function isNoopener(): bool
    {
        return $this->noopener;
    }

    public function setNoopener(bool $noopener): void
    {
        $this->noopener = $noopener;
    }

    public function isNoreferrer(): bool
    {
        return $this->noreferrer;
    }

    public function setNoreferrer(bool $noreferrer): void
    {
        $this->noreferrer = $noreferrer;
    }

    public function getTarget(): ?string
    {
        return $this->openInNewTab ? '_blank' : null;
    }

    public function getRel(): ?string
    {
        $rel = [];

        if ($this->nofollow) {
            $rel[] = 'nofollow';
        }

        if ($this->noopener) {
            $rel[] = 'noopener';
        }

        if ($this->noreferrer) {
            $rel[] = 'noreferrer';
        }

        return count($rel) > 0 ? implode(' ', $rel) : null;
    }
}
