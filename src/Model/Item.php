<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Setono\SyliusNavigationPlugin\Attribute\ItemType;
use Setono\SyliusNavigationPlugin\Form\Type\ItemType as ItemFormType;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\ToggleableTrait;
use Sylius\Component\Resource\Model\TranslatableTrait;

/**
 * @method ItemTranslationInterface getTranslation(?string $locale = null)
 */
#[ItemType(
    name: 'text',
    formType: ItemFormType::class,
    template: '@SetonoSyliusNavigationPlugin/navigation/build/form/_item.html.twig',
    label: 'Text Item',
    options: ['icon' => 'file text icon'],
)]
class Item implements ItemInterface
{
    use TimestampableTrait;
    use ToggleableTrait;
    use TranslatableTrait;

    protected ?int $id = null;

    protected ?NavigationInterface $navigation = null;

    protected int $position = 0;

    /** @var Collection<array-key, ChannelInterface> */
    protected Collection $channels;

    public function __construct()
    {
        $this->channels = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getLabel();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->getTranslation()->getLabel();
    }

    public function setLabel(?string $label): void
    {
        $this->getTranslation()->setLabel($label);
    }

    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function addChannel(ChannelInterface $channel): void
    {
        if (!$this->hasChannel($channel)) {
            $this->channels->add($channel);
        }
    }

    public function removeChannel(ChannelInterface $channel): void
    {
        if ($this->hasChannel($channel)) {
            $this->channels->removeElement($channel);
        }
    }

    public function hasChannel(ChannelInterface $channel): bool
    {
        return $this->channels->contains($channel);
    }

    public function getNavigation(): ?NavigationInterface
    {
        return $this->navigation;
    }

    public function setNavigation(?NavigationInterface $navigation): void
    {
        $this->navigation = $navigation;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    protected function createTranslation(): ItemTranslation
    {
        return new ItemTranslation();
    }
}
