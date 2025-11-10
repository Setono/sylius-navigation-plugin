<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Twig;

use Symfony\Bundle\TwigBundle\DependencyInjection\Configurator\EnvironmentConfigurator;
use Twig\Environment;
use Twig\Extension\EscaperExtension;
use Twig\Runtime\EscaperRuntime;

/**
 * Copied from \Symfony\UX\TwigComponent\Twig\TwigEnvironmentConfigurator
 */
final class TwigEnvironmentConfigurator
{
    public function __construct(private readonly EnvironmentConfigurator $decorated)
    {
    }

    public function configure(Environment $environment): void
    {
        $this->decorated->configure($environment);

        if (class_exists(EscaperRuntime::class)) {
            /** @phpstan-ignore method.notFound */
            $environment->getRuntime(EscaperRuntime::class)->addSafeClass(Attributes::class, ['html']);
        } elseif ($environment->hasExtension(EscaperExtension::class)) {
            /** @phpstan-ignore method.notFound */
            $environment->getExtension(EscaperExtension::class)->addSafeClass(Attributes::class, ['html']);
        }
    }
}
