<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Renderer;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Setono\SyliusNavigationPlugin\Model\NavigationInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CachedNavigationRenderer implements NavigationRendererInterface
{
    public function __construct(
        private readonly NavigationRendererInterface $decorated,
        private readonly CacheItemPoolInterface $cachePool,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
    ) {
    }

    public function render(
        NavigationInterface|string $navigation,
        ChannelInterface $channel = null,
        string $localeCode = null,
    ): string {
        $channel ??= $this->channelContext->getChannel();
        $localeCode ??= $this->localeContext->getLocaleCode();

        $cacheKey = sprintf('setono_navigation_%s_%s_%s', is_string($navigation) ? $navigation : $navigation->getCode(), $channel->getCode(), $localeCode);

        $cacheItem = $this->cachePool->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            $cachedValue = $cacheItem->get();
            if (is_string($cachedValue)) {
                return $cachedValue;
            }
        }

        $result = $this->decorated->render($navigation, $channel, $localeCode);

        $cacheItem->set($result);

        // Tag the cache item with the navigation code for easy invalidation
        if ($cacheItem instanceof ItemInterface && $this->cachePool instanceof TagAwareCacheInterface) {
            $cacheItem->tag(self::getTag($navigation));
        }

        $this->cachePool->save($cacheItem);

        return $result;
    }

    public function invalidate(NavigationInterface|string ...$navigations): void
    {
        if ([] === $navigations) {
            return;
        }

        if (!$this->cachePool instanceof TagAwareCacheInterface) {
            // Cache pool doesn't support tags, clear everything
            $this->invalidateAll();

            return;
        }

        try {
            $tags = array_unique(array_map(
                static fn (NavigationInterface|string $navigation): string => self::getTag($navigation),
                $navigations,
            ));
            $this->cachePool->invalidateTags($tags);
        } catch (InvalidArgumentException) {
            // Tag invalidation failed, fall back to clearing all cache
            $this->cachePool->clear();
        }
    }

    public function invalidateAll(): void
    {
        $this->cachePool->clear();
    }

    private static function getTag(NavigationInterface|string $navigation): string
    {
        return sprintf('setono_navigation_%s', is_string($navigation) ? $navigation : $navigation->getCode());
    }
}
