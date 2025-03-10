<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer\Item;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\CompositeCompilerPass\CompositeService;
use Setono\SyliusNavigationPlugin\Model\NavigationItemInterface;

/**
 * @extends CompositeService<NavigationItemRendererInterface>
 */
final class CompositeNavigationItemRenderer extends CompositeService implements NavigationItemRendererInterface, LoggerAwareInterface
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function render(NavigationItemInterface $item): string
    {
        foreach ($this->services as $service) {
            if ($service->supports($item)) {
                return $service->render($item);
            }
        }

        $this->logger->error('Could not find a navigation item renderer for item "{item}"', [
            'item' => $item::class,
        ]);

        return '';
    }

    public function supports(NavigationItemInterface $item): bool
    {
        foreach ($this->services as $service) {
            if ($service->supports($item)) {
                return true;
            }
        }

        return false;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
