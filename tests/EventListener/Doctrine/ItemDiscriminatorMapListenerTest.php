<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusNavigationPlugin\EventListener\Doctrine\ItemDiscriminatorMapListener;
use Setono\SyliusNavigationPlugin\Model\Item;
use Setono\SyliusNavigationPlugin\Model\ItemInterface;
use Setono\SyliusNavigationPlugin\Model\TaxonItem;

final class ItemDiscriminatorMapListenerTest extends TestCase
{
    use ProphecyTrait;
    /**
     * @test
     */
    public function it_sets_discriminator_map_for_item_class(): void
    {
        $resources = [
            'setono_sylius_navigation.item' => [
                'classes' => [
                    'model' => Item::class,
                ],
            ],
            'setono_sylius_navigation.taxon_item' => [
                'classes' => [
                    'model' => TaxonItem::class,
                ],
            ],
        ];

        $listener = new ItemDiscriminatorMapListener($resources);

        $metadata = new ClassMetadata(Item::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new LoadClassMetadataEventArgs($metadata, $entityManager);

        $listener->loadClassMetadata($eventArgs);

        self::assertIsArray($metadata->discriminatorMap);
        self::assertArrayHasKey('item', $metadata->discriminatorMap);
        self::assertArrayHasKey('taxon_item', $metadata->discriminatorMap);
        self::assertSame(Item::class, $metadata->discriminatorMap['item']);
        self::assertSame(TaxonItem::class, $metadata->discriminatorMap['taxon_item']);
    }

    /**
     * @test
     */
    public function it_does_nothing_for_non_item_classes(): void
    {
        $resources = [
            'setono_sylius_navigation.item' => [
                'classes' => [
                    'model' => Item::class,
                ],
            ],
        ];

        $listener = new ItemDiscriminatorMapListener($resources);

        $metadata = new ClassMetadata(\stdClass::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new LoadClassMetadataEventArgs($metadata, $entityManager);

        $listener->loadClassMetadata($eventArgs);

        self::assertEmpty($metadata->discriminatorMap);
    }

    /**
     * @test
     */
    public function it_skips_classes_that_do_not_implement_item_interface(): void
    {
        $resources = [
            'setono_sylius_navigation.item' => [
                'classes' => [
                    'model' => Item::class,
                ],
            ],
            'app.some_entity' => [
                'classes' => [
                    'model' => \stdClass::class,
                ],
            ],
        ];

        $listener = new ItemDiscriminatorMapListener($resources);

        $metadata = new ClassMetadata(Item::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new LoadClassMetadataEventArgs($metadata, $entityManager);

        $listener->loadClassMetadata($eventArgs);

        self::assertIsArray($metadata->discriminatorMap);
        self::assertArrayHasKey('item', $metadata->discriminatorMap);
        self::assertArrayNotHasKey('std_class', $metadata->discriminatorMap);
    }

    /**
     * @test
     */
    public function it_handles_empty_resources_array(): void
    {
        $listener = new ItemDiscriminatorMapListener([]);

        $metadata = new ClassMetadata(Item::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new LoadClassMetadataEventArgs($metadata, $entityManager);

        $listener->loadClassMetadata($eventArgs);

        self::assertIsArray($metadata->discriminatorMap);
        self::assertEmpty($metadata->discriminatorMap);
    }

    /**
     * @test
     */
    public function it_converts_class_names_to_snake_case_for_discriminator_keys(): void
    {
        $resources = [
            'setono_sylius_navigation.custom_item' => [
                'classes' => [
                    'model' => TestCustomNavigationItem::class,
                ],
            ],
        ];

        $listener = new ItemDiscriminatorMapListener($resources);

        $metadata = new ClassMetadata(Item::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new LoadClassMetadataEventArgs($metadata, $entityManager);

        $listener->loadClassMetadata($eventArgs);

        self::assertIsArray($metadata->discriminatorMap);
        self::assertArrayHasKey('test_custom_navigation_item', $metadata->discriminatorMap);
        self::assertSame(TestCustomNavigationItem::class, $metadata->discriminatorMap['test_custom_navigation_item']);
    }

    /**
     * @test
     */
    public function it_includes_only_item_interface_implementations(): void
    {
        $resources = [
            'setono_sylius_navigation.item' => [
                'classes' => [
                    'model' => Item::class,
                ],
            ],
            'setono_sylius_navigation.taxon_item' => [
                'classes' => [
                    'model' => TaxonItem::class,
                ],
            ],
            'app.non_item' => [
                'classes' => [
                    'model' => TestNonItemClass::class,
                ],
            ],
        ];

        $listener = new ItemDiscriminatorMapListener($resources);

        $metadata = new ClassMetadata(Item::class);
        $entityManager = $this->prophesize(EntityManagerInterface::class)->reveal();
        $eventArgs = new LoadClassMetadataEventArgs($metadata, $entityManager);

        $listener->loadClassMetadata($eventArgs);

        self::assertCount(2, $metadata->discriminatorMap);
        self::assertArrayHasKey('item', $metadata->discriminatorMap);
        self::assertArrayHasKey('taxon_item', $metadata->discriminatorMap);
        self::assertArrayNotHasKey('test_non_item_class', $metadata->discriminatorMap);
    }
}

// Test classes

class TestCustomNavigationItem extends Item
{
}

class TestNonItemClass
{
}
