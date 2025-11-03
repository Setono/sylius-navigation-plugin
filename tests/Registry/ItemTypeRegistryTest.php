<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Registry;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\Factory\ItemFactoryInterface;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Registry\ItemType;
use Setono\SyliusNavigationPlugin\Registry\ItemTypeRegistry;

final class ItemTypeRegistryTest extends TestCase
{
    use ProphecyTrait;

    private ItemTypeRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ItemTypeRegistry();
    }

    private function createMockFactory(): ItemFactoryInterface
    {
        return $this->prophesize(ItemFactoryInterface::class)->reveal();
    }

    /**
     * @test
     */
    public function it_registers_item_types(): void
    {
        $this->registry->register(
            'text',
            'Text Item',
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
            $this->createMockFactory(),
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
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
            $this->createMockFactory(),
        );

        $itemType = $this->registry->get('text');

        self::assertInstanceOf(ItemType::class, $itemType);
        self::assertSame('text', $itemType->name);
        self::assertSame('Text Item', $itemType->label);
        self::assertSame(Item::class, $itemType->entity);
        self::assertSame(MockFormType::class, $itemType->form);
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
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
            $this->createMockFactory(),
        );

        $this->registry->register(
            'taxon',
            'Taxon Item',
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_taxon.html.twig',
            $this->createMockFactory(),
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
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
            $this->createMockFactory(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An item type with name "text" is already registered');
        $this->expectExceptionMessage(Item::class);

        $this->registry->register(
            'text',
            'Another Text Item',
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_other.html.twig',
            $this->createMockFactory(),
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
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
            $this->createMockFactory(),
        );

        $this->registry->register(
            'taxon',
            'Taxon Item',
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_taxon.html.twig',
            $this->createMockFactory(),
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
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_first.html.twig',
            $this->createMockFactory(),
        );

        $this->registry->register(
            'second',
            'Second Item',
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_second.html.twig',
            $this->createMockFactory(),
        );

        $this->registry->register(
            'third',
            'Third Item',
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_third.html.twig',
            $this->createMockFactory(),
        );

        $all = $this->registry->all();
        $keys = array_keys($all);

        self::assertSame(['first', 'second', 'third'], $keys);
    }

    /**
     * @test
     */
    public function it_retrieves_item_type_by_entity_class(): void
    {
        $this->registry->register(
            'text',
            'Text Item',
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
            $this->createMockFactory(),
        );

        $itemType = $this->registry->getByEntity(Item::class);

        self::assertInstanceOf(ItemType::class, $itemType);
        self::assertSame('text', $itemType->name);
        self::assertSame('Text Item', $itemType->label);
        self::assertSame(Item::class, $itemType->entity);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_entity_class_is_not_registered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No item type registered for entity class "NonExistentClass"');

        $this->registry->getByEntity('NonExistentClass');
    }

    /**
     * @test
     */
    public function it_includes_available_entities_in_exception_message_when_entity_not_found(): void
    {
        $this->registry->register(
            'text',
            'Text Item',
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
            $this->createMockFactory(),
        );

        $this->registry->register(
            'link',
            'Link Item',
            MockLinkItem::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_link.html.twig',
            $this->createMockFactory(),
        );

        try {
            $this->registry->getByEntity('NonExistentClass');
            self::fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString(Item::class, $e->getMessage());
            self::assertStringContainsString(MockLinkItem::class, $e->getMessage());
            self::assertStringContainsString('(text)', $e->getMessage());
            self::assertStringContainsString('(link)', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_matches_entity_exactly_without_checking_inheritance(): void
    {
        // Register a base class
        $this->registry->register(
            'text',
            'Text Item',
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
            $this->createMockFactory(),
        );

        // Try to get by subclass - should fail because we only do exact matching
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No item type registered for entity class');

        $this->registry->getByEntity(MockLinkItem::class);
    }

    /**
     * @test
     */
    public function it_returns_correct_item_type_when_multiple_types_are_registered(): void
    {
        $this->registry->register(
            'text',
            'Text Item',
            Item::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_text.html.twig',
            $this->createMockFactory(),
        );

        $this->registry->register(
            'link',
            'Link Item',
            MockLinkItem::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_link.html.twig',
            $this->createMockFactory(),
        );

        $this->registry->register(
            'taxon',
            'Taxon Item',
            MockTaxonItem::class,
            MockFormType::class,
            '@SetonoSyliusNavigationPlugin/navigation/build/form/_taxon.html.twig',
            $this->createMockFactory(),
        );

        // Verify each entity returns its specific type
        $textType = $this->registry->getByEntity(Item::class);
        self::assertSame('text', $textType->name);

        $linkType = $this->registry->getByEntity(MockLinkItem::class);
        self::assertSame('link', $linkType->name);

        $taxonType = $this->registry->getByEntity(MockTaxonItem::class);
        self::assertSame('taxon', $taxonType->name);
    }
}

// Mock form type class for testing purposes
class MockFormType
{
}

// Mock entity classes for testing
class MockLinkItem extends Item
{
}

class MockTaxonItem extends Item
{
}
