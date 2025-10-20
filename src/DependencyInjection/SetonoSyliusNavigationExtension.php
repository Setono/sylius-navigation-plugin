<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusNavigationExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{resources: array} $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $this->registerResources('setono_sylius_navigation', SyliusResourceBundle::DRIVER_DOCTRINE_ORM, $config['resources'], $container);

        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('sylius_grid', [
            'grids' => [
                'setono_sylius_navigation_admin_navigation' => [
                    'driver' => [
                        'name' => SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
                        'options' => [
                            'class' => '%setono_sylius_navigation.model.navigation.class%',
                        ],
                    ],
                    'limits' => [100, 250, 500, 1000],
                    'fields' => [
                        'code' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.code',
                        ],
                        'description' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.description',
                        ],
                    ],
                    'actions' => [
                        'main' => [
                            'create' => [
                                'type' => 'create',
                            ],
                        ],
                        'item' => [
                            'update' => [
                                'type' => 'update',
                            ],
                            'delete' => [
                                'type' => 'delete',
                            ],
                        ],
                        'bulk' => [
                            'delete' => [
                                'type' => 'delete',
                            ],
                        ],
                    ],
                ],
                'setono_sylius_navigation_admin_item' => [
                    'driver' => [
                        'name' => SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
                        'options' => [
                            'class' => '%setono_sylius_navigation.model.item.class%',
                        ],
                    ],
                    'limits' => [100, 250, 500, 1000],
                    'fields' => [
                        'label' => [
                            'type' => 'string',
                            'label' => 'sylius.ui.name',
                        ],
                        'enabled' => [
                            'type' => 'twig',
                            'label' => 'sylius.ui.enabled',
                            'options' => [
                                'template' => '@SyliusUi/Grid/Field/enabled.html.twig',
                            ],
                        ],
                    ],
                    'actions' => [
                        'item' => [
                            'update' => [
                                'type' => 'update',
                            ],
                            'delete' => [
                                'type' => 'delete',
                            ],
                        ],
                        'bulk' => [
                            'delete' => [
                                'type' => 'delete',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
