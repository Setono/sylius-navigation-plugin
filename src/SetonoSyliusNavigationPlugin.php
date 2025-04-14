<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin;

use Setono\CompositeCompilerPass\CompositeCompilerPass;
use Setono\SyliusNavigationPlugin\DependencyInjection\Compiler\ResolveTargetEntitiesPass;
use Setono\SyliusNavigationPlugin\Renderer\Item\CompositeItemRenderer;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusNavigationPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    public function getSupportedDrivers(): array
    {
        return [SyliusResourceBundle::DRIVER_DOCTRINE_ORM];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ResolveTargetEntitiesPass());

        $container->addCompilerPass(new CompositeCompilerPass(
            CompositeItemRenderer::class,
            'setono_sylius_navigation.item_renderer',
        ));
    }
}
