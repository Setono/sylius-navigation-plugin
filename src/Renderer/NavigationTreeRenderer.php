<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer;

use Setono\SyliusNavigationPlugin\Model\NavigationItemInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Twig\Environment;

final class NavigationTreeRenderer implements NavigationTreeRendererInterface
{
    public function __construct(
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
        private readonly Environment $twig,
        private readonly string $template = '@SetonoSyliusNavigationPlugin/navigation/tree.html.twig',
    ) {
    }

    public function render(NavigationItemInterface $item, ChannelInterface $channel = null, string $localeCode = null): string
    {
        $maxDepth = $item->getNavigation()?->getMaxDepth();
        if (null !== $maxDepth && $item->getLevel() > $maxDepth) {
            return '';
        }

        $channel = $channel ?? $this->channelContext->getChannel();
        $localeCode = $localeCode ?? $this->localeContext->getLocaleCode();

        if (!$item->hasChannel($channel)) {
            return '';
        }

        $item->setCurrentLocale($localeCode);

        return $this->twig->render($this->template, [
            'item' => $item,
            'channel' => $channel,
            'localeCode' => $localeCode,
        ]);
    }
}
