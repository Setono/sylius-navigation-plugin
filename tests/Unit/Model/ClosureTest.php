<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusNavigationPlugin\Model\Closure;
use Setono\SyliusNavigationPlugin\Model\Item;

final class ClosureTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_null_id_by_default(): void
    {
        $closure = new Closure();

        self::assertNull($closure->getId());
    }

    /**
     * @test
     */
    public function it_has_no_ancestor_by_default(): void
    {
        $closure = new Closure();

        self::assertNull($closure->getAncestor());
    }

    /**
     * @test
     */
    public function it_allows_setting_ancestor(): void
    {
        $item = new Item();

        $closure = new Closure();
        $closure->setAncestor($item);

        self::assertSame($item, $closure->getAncestor());
    }

    /**
     * @test
     */
    public function it_allows_setting_ancestor_to_null(): void
    {
        $item = new Item();

        $closure = new Closure();
        $closure->setAncestor($item);
        $closure->setAncestor(null);

        self::assertNull($closure->getAncestor());
    }

    /**
     * @test
     */
    public function it_has_no_descendant_by_default(): void
    {
        $closure = new Closure();

        self::assertNull($closure->getDescendant());
    }

    /**
     * @test
     */
    public function it_allows_setting_descendant(): void
    {
        $item = new Item();

        $closure = new Closure();
        $closure->setDescendant($item);

        self::assertSame($item, $closure->getDescendant());
    }

    /**
     * @test
     */
    public function it_allows_setting_descendant_to_null(): void
    {
        $item = new Item();

        $closure = new Closure();
        $closure->setDescendant($item);
        $closure->setDescendant(null);

        self::assertNull($closure->getDescendant());
    }

    /**
     * @test
     */
    public function it_has_zero_depth_by_default(): void
    {
        $closure = new Closure();

        self::assertSame(0, $closure->getDepth());
    }

    /**
     * @test
     */
    public function it_allows_setting_depth(): void
    {
        $closure = new Closure();
        $closure->setDepth(3);

        self::assertSame(3, $closure->getDepth());
    }

    /**
     * @test
     */
    public function it_allows_different_items_for_ancestor_and_descendant(): void
    {
        $ancestor = new Item();
        $descendant = new Item();

        $closure = new Closure();
        $closure->setAncestor($ancestor);
        $closure->setDescendant($descendant);

        self::assertSame($ancestor, $closure->getAncestor());
        self::assertSame($descendant, $closure->getDescendant());
        self::assertNotSame($closure->getAncestor(), $closure->getDescendant());
    }
}
