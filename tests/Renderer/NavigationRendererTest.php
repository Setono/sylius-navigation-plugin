<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Setono\SyliusNavigationPlugin\Model\Navigation;
use Setono\SyliusNavigationPlugin\Renderer\NavigationRenderer;
use Setono\SyliusNavigationPlugin\Repository\NavigationRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\Channel;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class NavigationRendererTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<NavigationRepositoryInterface> */
    private ObjectProphecy $navigationRepository;

    /** @var ObjectProphecy<ChannelContextInterface> */
    private ObjectProphecy $channelContext;

    /** @var ObjectProphecy<LocaleContextInterface> */
    private ObjectProphecy $localeContext;

    private NavigationRenderer $renderer;

    protected function setUp(): void
    {
        $this->navigationRepository = $this->prophesize(NavigationRepositoryInterface::class);

        $channel = new Channel();
        $channel->setCode('WEB');

        $this->channelContext = $this->prophesize(ChannelContextInterface::class);
        $this->channelContext->getChannel()->willReturn($channel);

        $this->localeContext = $this->prophesize(LocaleContextInterface::class);
        $this->localeContext->getLocaleCode()->willReturn('en_US');

        $this->renderer = new NavigationRenderer(
            $this->navigationRepository->reveal(),
            $this->channelContext->reveal(),
            $this->localeContext->reveal(),
            new Environment(new ArrayLoader([
                'navigation.html.twig' => '<nav data-channel="{{ channel.code }}" data-locale-code="{{ localeCode }}">{{ navigation.code }}</nav>',
            ])),
            'navigation.html.twig',
        );
    }

    /**
     * @test
     */
    public function it_renders_from_object(): void
    {
        $navigation = new Navigation();
        $navigation->setCode('navigation_top');

        self::assertSame('<nav data-channel="WEB" data-locale-code="en_US">navigation_top</nav>', $this->renderer->render($navigation));
    }

    /**
     * @test
     */
    public function it_renders_from_code(): void
    {
        $navigation = new Navigation();
        $navigation->setCode('navigation_bottom');

        $this->navigationRepository
            ->findOneEnabledByCode('navigation_bottom', Argument::type(ChannelInterface::class))
            ->willReturn($navigation)
        ;

        self::assertSame('<nav data-channel="WEB" data-locale-code="en_US">navigation_bottom</nav>', $this->renderer->render('navigation_bottom'));
    }
}
