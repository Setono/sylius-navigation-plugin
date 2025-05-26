<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusNavigationPlugin\Graph\GraphBuilderInterface;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Twig\Environment;

// todo cache this
final class NavigationRenderer implements NavigationRendererInterface, LoggerAwareInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly NavigationRepositoryInterface $navigationRepository,
        private readonly GraphBuilderInterface $graphBuilder,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
        private readonly Environment $twig,
        private readonly string $defaultTemplate = '@SetonoSyliusNavigationPlugin/navigation/navigation.html.twig',
    ) {
        $this->logger = new NullLogger();
    }

    public function render(
        NavigationInterface|string $navigation,
        ChannelInterface $channel = null,
        string $localeCode = null,
    ): string {
        $channel ??= $this->channelContext->getChannel();
        $localeCode ??= $this->localeContext->getLocaleCode();

        if (is_string($navigation)) {
            $navigation = $this->navigationRepository->findOneEnabledByCode($navigation, $channel);
            if (null === $navigation) {
                $this->logger->error('Could not find navigation with code {code} on channel "{channel}"', [
                    'code' => $navigation,
                    'channel' => $channel->getCode(),
                ]);

                return '';
            }
        }

        $template = $this->twig->resolveTemplate([
            sprintf('@SetonoSyliusNavigationPlugin/navigation/navigation.%s.html.twig', (string) $navigation->getCode()),
            $this->defaultTemplate,
        ]);

        return $this->twig->render($template, [
            'navigation' => $navigation,
            'graph' => $this->graphBuilder->build($navigation),
            'channel' => $channel,
            'localeCode' => $localeCode,
        ]);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
