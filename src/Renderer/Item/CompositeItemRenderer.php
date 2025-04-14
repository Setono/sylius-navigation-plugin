<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer\Item;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\CompositeCompilerPass\CompositeService;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;

/**
 * @extends CompositeService<ItemRendererInterface>
 */
final class CompositeItemRenderer extends CompositeService implements ItemRendererInterface, LoggerAwareInterface
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function render(ItemInterface $item, array $attributes = []): string
    {
        foreach ($this->services as $service) {
            if ($service->supports($item)) {
                return $service->render($item, $attributes);
            }
        }

        $this->logger->error('Could not find a navigation item renderer for item "{item}"', [
            'item' => $item::class,
        ]);

        return '';
    }

    public function supports(ItemInterface $item): bool
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
