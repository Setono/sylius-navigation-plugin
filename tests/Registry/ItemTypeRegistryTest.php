<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Registry;

use PHPUnit\Framework\TestCase;
use Setono\SyliusNavigationPlugin\Form\Type\BuilderTextItemType;
use Setono\SyliusNavigationPlugin\Model\TextItem;
use Setono\SyliusNavigationPlugin\Registry\ItemType;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistry;

final class ItemTypeRegistryTest extends TestCase
{
    private ItemTypeRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ItemTypeRegistry();
    }

    /**
     * @test
     */
    public function it_registers_item_types(): void
    {
        $this->registry->register(
            'text',
            'Text Item',
            TextItem::class,
            BuilderTextItemType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
        );

        self::assertTrue($this->registry->has('text'));
    }

    /**
     * @test
     */
    public function it_retrieves_registered_item_type(): void
    {
        $this->registry->register(
            'text',
            'Text Item',
            TextItem::class,
            BuilderTextItemType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
        );

        $itemType = $this->registry->get('text');

        self::assertInstanceOf(ItemType::class, $itemType);
        self::assertSame('text', $itemType->name);
        self::assertSame('Text Item', $itemType->label);
        self::assertSame(TextItem::class, $itemType->entity);
        self::assertSame(BuilderTextItemType::class, $itemType->form);
        self::assertSame('@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig', $itemType->template);
    }

    /**
     * @test
     */
    public function it_returns_false_when_item_type_is_not_registered(): void
    {
        self::assertFalse($this->registry->has('non_existent'));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_getting_non_existent_item_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No item type registered with name "non_existent". Available types:');

        $this->registry->get('non_existent');
    }

    /**
     * @test
     */
    public function it_includes_available_types_in_exception_message(): void
    {
        $this->registry->register(
            'text',
            'Text Item',
            TextItem::class,
            BuilderTextItemType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
        );

        $this->registry->register(
            'taxon',
            'Taxon Item',
            TextItem::class,
            BuilderTextItemType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_taxon.html.twig',
        );

        try {
            $this->registry->get('non_existent');
            self::fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('text, taxon', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_throws_exception_when_registering_duplicate_item_type(): void
    {
        $this->registry->register(
            'text',
            'Text Item',
            TextItem::class,
            BuilderTextItemType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An item type with name "text" is already registered');
        $this->expectExceptionMessage(TextItem::class);

        $this->registry->register(
            'text',
            'Another Text Item',
            TextItem::class,
            BuilderTextItemType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_other.html.twig',
        );
    }

    /**
     * @test
     */
    public function it_returns_all_registered_item_types(): void
    {
        $this->registry->register(
            'text',
            'Text Item',
            TextItem::class,
            BuilderTextItemType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
        );

        $this->registry->register(
            'taxon',
            'Taxon Item',
            TextItem::class,
            BuilderTextItemType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_taxon.html.twig',
        );

        $all = $this->registry->all();

        self::assertIsArray($all);
        self::assertCount(2, $all);
        self::assertArrayHasKey('text', $all);
        self::assertArrayHasKey('taxon', $all);
        self::assertInstanceOf(ItemType::class, $all['text']);
        self::assertInstanceOf(ItemType::class, $all['taxon']);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_when_no_types_are_registered(): void
    {
        $all = $this->registry->all();

        self::assertIsArray($all);
        self::assertEmpty($all);
    }

    /**
     * @test
     */
    public function it_preserves_registration_order_in_all(): void
    {
        $this->registry->register(
            'first',
            'First Item',
            TextItem::class,
            BuilderTextItemType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_first.html.twig',
        );

        $this->registry->register(
            'second',
            'Second Item',
            TextItem::class,
            BuilderTextItemType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_second.html.twig',
        );

        $this->registry->register(
            'third',
            'Third Item',
            TextItem::class,
            BuilderTextItemType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_third.html.twig',
        );

        $all = $this->registry->all();
        $keys = array_keys($all);

        self::assertSame(['first', 'second', 'third'], $keys);
    }
}
