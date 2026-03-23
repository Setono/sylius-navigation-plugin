<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusNavigationPlugin\Model\ItemTranslation;

final class ItemTranslationTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_null_id_by_default(): void
    {
        $translation = new ItemTranslation();

        self::assertNull($translation->getId());
    }

    /**
     * @test
     */
    public function it_has_no_label_by_default(): void
    {
        $translation = new ItemTranslation();

        self::assertNull($translation->getLabel());
    }

    /**
     * @test
     */
    public function it_allows_setting_label(): void
    {
        $translation = new ItemTranslation();
        $translation->setLabel('Home');

        self::assertSame('Home', $translation->getLabel());
    }

    /**
     * @test
     */
    public function it_allows_setting_label_to_null(): void
    {
        $translation = new ItemTranslation();
        $translation->setLabel('Home');
        $translation->setLabel(null);

        self::assertNull($translation->getLabel());
    }

    /**
     * @test
     */
    public function it_converts_empty_string_label_to_null(): void
    {
        $translation = new ItemTranslation();
        $translation->setLabel('');

        self::assertNull($translation->getLabel());
    }

    /**
     * @test
     */
    public function it_keeps_non_empty_label_as_is(): void
    {
        $translation = new ItemTranslation();
        $translation->setLabel('About');

        self::assertSame('About', $translation->getLabel());
    }
}
