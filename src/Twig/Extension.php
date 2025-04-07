<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ssn_navigation', [Runtime::class, 'navigation'], ['is_safe' => ['html']]),
            new TwigFunction('ssn_item', [Runtime::class, 'item'], ['is_safe' => ['html']]),
        ];
    }
}
