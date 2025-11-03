<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Renderer\Item;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Renderer\Item\DefaultItemRenderer;

final class DefaultItemRendererTest extends TestCase
{
    use ProphecyTrait;

    private ItemInterface $item;

    protected function setUp(): void
    {
        $this->item = $this->createItem();
    }

    /**
     * @test
     */
    public function it_always_supports_any_item(): void
    {
        $twig = $this->prophesize(\Twig\Environment::class)->reveal();
        $renderer = new DefaultItemRenderer($twig);

        self::assertTrue($renderer->supports($this->item));
        self::assertTrue($renderer->supports($this->createCustomItem()));
    }

    /**
     * @test
     */
    public function it_uses_default_template_path(): void
    {
        $twig = $this->prophesize(\Twig\Environment::class)->reveal();
        $renderer = new DefaultItemRenderer($twig);

        $reflection = new \ReflectionClass($renderer);
        $property = $reflection->getProperty('defaultTemplate');

        self::assertSame(
            '@SetonoSyliusNavigationPlugin/navigation/item/default.html.twig',
            $property->getValue($renderer),
        );
    }

    /**
     * @test
     */
    public function it_uses_custom_default_template_when_provided(): void
    {
        $customTemplate = '@App/custom_item_template.html.twig';
        $twig = $this->prophesize(\Twig\Environment::class)->reveal();
        $renderer = new DefaultItemRenderer($twig, $customTemplate);

        $reflection = new \ReflectionClass($renderer);
        $property = $reflection->getProperty('defaultTemplate');

        self::assertSame($customTemplate, $property->getValue($renderer));
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

    private function createCustomItem(): ItemInterface
    {
        $item = new class() extends Item {
            public static function getType(string|ItemInterface $item = null): string
            {
                return 'custom_item';
            }
        };

        $item->setCurrentLocale('en_US');
        $item->setFallbackLocale('en_US');
        $item->setLabel('Custom Item');

        return $item;
    }
}
