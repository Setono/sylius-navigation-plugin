<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Setono\SyliusNavigationPlugin\Twig\Attributes;

final class AttributesTest extends TestCase
{
    /** @test */
    public function it_converts_to_string(): void
    {
        $attributes = new Attributes([
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
            'disabled' => true,
            'hidden' => false,
        ]);

        $expected = ' class="btn btn-primary" id="submit-button" disabled';
        self::assertSame($expected, (string) $attributes);
    }

    /** @test */
    public function it_renders_specific_attribute(): void
    {
        $attributes = new Attributes([
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
        ]);

        self::assertSame('btn btn-primary', $attributes->render('class'));
        self::assertSame('submit-button', $attributes->render('id'));
        self::assertNull($attributes->render('data-id'));
    }

    /** @test */
    public function it_filters_attributes(): void
    {
        $attributes = new Attributes([
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
            'data-id' => '123',
        ]);

        $filtered = $attributes->only('class', 'id');
        self::assertCount(2, $filtered);
        self::assertArrayHasKey('class', $filtered->all());
        self::assertArrayHasKey('id', $filtered->all());
        self::assertArrayNotHasKey('data-id', $filtered->all());

        $without = $attributes->without('class');
        self::assertCount(2, $without);
        self::assertArrayNotHasKey('class', $without->all());
        self::assertArrayHasKey('id', $without->all());
        self::assertArrayHasKey('data-id', $without->all());
    }
}
