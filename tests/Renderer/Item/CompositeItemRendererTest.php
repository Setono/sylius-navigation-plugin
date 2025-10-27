<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Renderer\Item;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Renderer\Item\CompositeItemRenderer;
use Setono\SyliusNavigationPlugin\Renderer\Item\ItemRendererInterface;

final class CompositeItemRendererTest extends TestCase
{
    use ProphecyTrait;

    private CompositeItemRenderer $compositeRenderer;

    private ItemInterface $item;

    protected function setUp(): void
    {
        $this->compositeRenderer = new CompositeItemRenderer();
        $this->item = $this->createItem();
    }

    /**
     * @test
     */
    public function it_renders_with_first_supporting_renderer(): void
    {
        $renderer1 = $this->createRenderer(false, '');
        $renderer2 = $this->createRenderer(true, '<li>Item</li>');
        $renderer3 = $this->createRenderer(true, '<div>Item</div>'); // Should not be called

        $this->compositeRenderer->add($renderer1->reveal());
        $this->compositeRenderer->add($renderer2->reveal());
        $this->compositeRenderer->add($renderer3->reveal());

        $result = $this->compositeRenderer->render($this->item);

        self::assertSame('<li>Item</li>', $result);

        // Verify renderer1 was checked but not used
        $renderer1->supports($this->item)->shouldHaveBeenCalled();
        $renderer1->render(Argument::cetera())->shouldNotHaveBeenCalled();

        // Verify renderer2 was used
        $renderer2->supports($this->item)->shouldHaveBeenCalled();
        $renderer2->render($this->item, [])->shouldHaveBeenCalled();

        // Verify renderer3 was never checked (short-circuit)
        $renderer3->supports(Argument::cetera())->shouldNotHaveBeenCalled();
        $renderer3->render(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_passes_attributes_to_renderer(): void
    {
        $attributes = ['class' => 'nav-item', 'data-id' => '123'];

        $renderer = $this->createRenderer(true, '<li class="nav-item">Item</li>');

        $this->compositeRenderer->add($renderer->reveal());

        $result = $this->compositeRenderer->render($this->item, $attributes);

        self::assertSame('<li class="nav-item">Item</li>', $result);
        $renderer->render($this->item, $attributes)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_returns_empty_string_when_no_renderer_supports_item(): void
    {
        $renderer1 = $this->createRenderer(false, '');
        $renderer2 = $this->createRenderer(false, '');

        $this->compositeRenderer->add($renderer1->reveal());
        $this->compositeRenderer->add($renderer2->reveal());

        $result = $this->compositeRenderer->render($this->item);

        self::assertSame('', $result);

        // Both renderers should have been checked
        $renderer1->supports($this->item)->shouldHaveBeenCalled();
        $renderer2->supports($this->item)->shouldHaveBeenCalled();

        // Neither should have been used to render
        $renderer1->render(Argument::cetera())->shouldNotHaveBeenCalled();
        $renderer2->render(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_logs_error_when_no_renderer_supports_item(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $this->compositeRenderer->setLogger($logger->reveal());

        $renderer = $this->createRenderer(false, '');
        $this->compositeRenderer->add($renderer->reveal());

        $this->compositeRenderer->render($this->item);

        $logger->error(
            'Could not find a navigation item renderer for item "{item}"',
            ['item' => $this->item::class],
        )->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_does_not_log_when_renderer_found(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $this->compositeRenderer->setLogger($logger->reveal());

        $renderer = $this->createRenderer(true, '<li>Item</li>');
        $this->compositeRenderer->add($renderer->reveal());

        $this->compositeRenderer->render($this->item);

        $logger->error(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_supports_item_when_any_renderer_supports_it(): void
    {
        $renderer1 = $this->createRenderer(false, '');
        $renderer2 = $this->createRenderer(true, '<li>Item</li>');

        $this->compositeRenderer->add($renderer1->reveal());
        $this->compositeRenderer->add($renderer2->reveal());

        $result = $this->compositeRenderer->supports($this->item);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function it_does_not_support_item_when_no_renderer_supports_it(): void
    {
        $renderer1 = $this->createRenderer(false, '');
        $renderer2 = $this->createRenderer(false, '');

        $this->compositeRenderer->add($renderer1->reveal());
        $this->compositeRenderer->add($renderer2->reveal());

        $result = $this->compositeRenderer->supports($this->item);

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function it_supports_item_with_first_supporting_renderer(): void
    {
        $renderer1 = $this->createRenderer(false, '');
        $renderer2 = $this->createRenderer(true, '<li>Item</li>');
        $renderer3 = $this->createRenderer(true, '<div>Item</div>');

        $this->compositeRenderer->add($renderer1->reveal());
        $this->compositeRenderer->add($renderer2->reveal());
        $this->compositeRenderer->add($renderer3->reveal());

        $result = $this->compositeRenderer->supports($this->item);

        self::assertTrue($result);

        // Verify short-circuit behavior
        $renderer1->supports($this->item)->shouldHaveBeenCalled();
        $renderer2->supports($this->item)->shouldHaveBeenCalled();
        $renderer3->supports(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_returns_false_when_no_renderers_added(): void
    {
        $result = $this->compositeRenderer->supports($this->item);

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function it_returns_empty_string_when_no_renderers_added(): void
    {
        $result = $this->compositeRenderer->render($this->item);

        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function it_sets_logger(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);

        $this->compositeRenderer->setLogger($logger->reveal());

        // Trigger a scenario that would log
        $this->compositeRenderer->render($this->item);

        $logger->error(Argument::any(), Argument::any())->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function it_uses_null_logger_by_default(): void
    {
        // This should not throw any errors even though no logger is explicitly set
        $result = $this->compositeRenderer->render($this->item);

        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function it_checks_all_renderers_when_none_support(): void
    {
        $renderer1 = $this->createRenderer(false, '');
        $renderer2 = $this->createRenderer(false, '');
        $renderer3 = $this->createRenderer(false, '');

        $this->compositeRenderer->add($renderer1->reveal());
        $this->compositeRenderer->add($renderer2->reveal());
        $this->compositeRenderer->add($renderer3->reveal());

        $this->compositeRenderer->render($this->item);

        // All renderers should have been checked
        $renderer1->supports($this->item)->shouldHaveBeenCalled();
        $renderer2->supports($this->item)->shouldHaveBeenCalled();
        $renderer3->supports($this->item)->shouldHaveBeenCalled();
    }

    /**
     * @return ObjectProphecy<ItemRendererInterface>
     */
    private function createRenderer(bool $supports, string $renderedOutput): ObjectProphecy
    {
        $renderer = $this->prophesize(ItemRendererInterface::class);
        $renderer->supports(Argument::any())->willReturn($supports);
        $renderer->render(Argument::any(), Argument::any())->willReturn($renderedOutput);

        return $renderer;
    }

    private function createItem(): ItemInterface
    {
        $item = new class() extends Item {
        };

        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setLabel('Test Item');

        return $item;
    }
}
