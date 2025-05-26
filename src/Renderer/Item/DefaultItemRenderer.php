<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer\Item;

use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Twig\Attributes;
use Twig\Environment;

final class DefaultItemRenderer implements ItemRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $defaultTemplate = '@SetonoSyliusNavigationPlugin/navigation/item/default.html.twig',
    ) {
    }

    public function render(ItemInterface $item, array $attributes = []): string
    {
        $template = $this->twig->resolveTemplate([
            sprintf('@SetonoSyliusNavigationPlugin/navigation/item/%s.html.twig', Item::getType($item::class)),
            $this->defaultTemplate,
        ]);

        /** @psalm-suppress ImplicitToStringCast */
        return $this->twig->render($template, [
            'item' => $item,
            'attributes' => new Attributes($attributes),
        ]);
    }

    public function supports(ItemInterface $item): bool
    {
        return true;
    }
}
