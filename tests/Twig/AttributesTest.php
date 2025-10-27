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

    /** @test */
    public function it_returns_all_attributes(): void
    {
        $attributesArray = [
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
            'data-id' => '123',
        ];

        $attributes = new Attributes($attributesArray);

        self::assertSame($attributesArray, $attributes->all());
    }

    /** @test */
    public function it_removes_single_attribute(): void
    {
        $attributes = new Attributes([
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
            'data-id' => '123',
        ]);

        $removed = $attributes->remove('id');

        self::assertCount(2, $removed);
        self::assertArrayHasKey('class', $removed->all());
        self::assertArrayNotHasKey('id', $removed->all());
        self::assertArrayHasKey('data-id', $removed->all());

        // Original should be unchanged
        self::assertCount(3, $attributes);
        self::assertTrue($attributes->has('id'));
    }

    /** @test */
    public function it_checks_if_attribute_exists(): void
    {
        $attributes = new Attributes([
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
        ]);

        self::assertTrue($attributes->has('class'));
        self::assertTrue($attributes->has('id'));
        self::assertFalse($attributes->has('data-id'));
        self::assertFalse($attributes->has('nonexistent'));
    }

    /** @test */
    public function it_counts_attributes(): void
    {
        $attributes = new Attributes([
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
            'data-id' => '123',
        ]);

        self::assertCount(3, $attributes);

        $removed = $attributes->remove('id');
        self::assertCount(2, $removed);
    }

    /** @test */
    public function it_iterates_over_attributes(): void
    {
        $attributesArray = [
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
            'data-id' => '123',
        ];

        $attributes = new Attributes($attributesArray);

        $result = [];
        foreach ($attributes as $key => $value) {
            $result[$key] = $value;
        }

        self::assertSame($attributesArray, $result);
    }

    /** @test */
    public function it_renders_stringable_objects(): void
    {
        $stringable = new class() implements \Stringable {
            public function __toString(): string
            {
                return 'stringable-value';
            }
        };

        $attributes = new Attributes([
            'data-value' => $stringable,
        ]);

        self::assertSame('stringable-value', $attributes->render('data-value'));
    }

    /** @test */
    public function it_renders_aria_attributes_with_boolean_true_as_string_true(): void
    {
        $attributes = new Attributes([
            'aria-hidden' => true,
            'aria-expanded' => true,
            'disabled' => true,
        ]);

        self::assertSame('true', $attributes->render('aria-hidden'));
        self::assertSame('true', $attributes->render('aria-expanded'));
    }

    /** @test */
    public function it_throws_exception_when_rendering_non_string_attribute(): void
    {
        $attributes = new Attributes([
            'disabled' => true,
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only get string attributes (disabled is a "bool")');

        $attributes->render('disabled');
    }

    /** @test */
    public function it_marks_rendered_attributes_to_prevent_duplication(): void
    {
        $attributes = new Attributes([
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
        ]);

        // Render specific attribute
        $attributes->render('class');

        // Convert to string - should not include 'class' since it was already rendered
        $result = (string) $attributes;

        self::assertSame(' id="submit-button"', $result);
    }

    /** @test */
    public function it_prevents_duplicate_rendering_in_to_string(): void
    {
        $attributes = new Attributes([
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
        ]);

        $first = (string) $attributes;
        $second = (string) $attributes;

        self::assertSame(' class="btn btn-primary" id="submit-button"', $first);
        self::assertSame('', $second);
    }

    /** @test */
    public function it_resets_rendered_attributes_on_clone(): void
    {
        $attributes = new Attributes([
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
        ]);

        // Render all attributes
        $first = (string) $attributes;
        self::assertSame(' class="btn btn-primary" id="submit-button"', $first);

        // Second call returns empty since all are rendered
        $second = (string) $attributes;
        self::assertSame('', $second);

        // Clone should reset rendered state
        $cloned = clone $attributes;
        $third = (string) $cloned;
        self::assertSame(' class="btn btn-primary" id="submit-button"', $third);
    }

    /** @test */
    public function it_converts_stringable_to_string_in_to_string(): void
    {
        $stringable = new class() implements \Stringable {
            public function __toString(): string
            {
                return 'custom-class';
            }
        };

        $attributes = new Attributes([
            'class' => $stringable,
            'id' => 'submit-button',
        ]);

        $result = (string) $attributes;

        self::assertSame(' class="custom-class" id="submit-button"', $result);
    }

    /** @test */
    public function it_handles_false_boolean_values(): void
    {
        $attributes = new Attributes([
            'class' => 'btn',
            'disabled' => false,
            'hidden' => false,
        ]);

        $result = (string) $attributes;

        // False values should be omitted
        self::assertSame(' class="btn"', $result);
    }

    /** @test */
    public function it_handles_true_boolean_values(): void
    {
        $attributes = new Attributes([
            'class' => 'btn',
            'disabled' => true,
            'required' => true,
        ]);

        $result = (string) $attributes;

        // True values should render as attribute name only (HTML5 boolean attributes)
        self::assertStringContainsString('class="btn"', $result);
        self::assertStringContainsString('disabled', $result);
        self::assertStringContainsString('required', $result);
        self::assertStringNotContainsString('disabled="', $result);
        self::assertStringNotContainsString('required="', $result);
    }

    /** @test */
    public function it_returns_null_when_rendering_nonexistent_attribute(): void
    {
        $attributes = new Attributes([
            'class' => 'btn',
        ]);

        self::assertNull($attributes->render('nonexistent'));
    }

    /** @test */
    public function it_handles_numeric_attribute_values(): void
    {
        $attributes = new Attributes([
            'data-count' => 42,
            'tabindex' => 0,
        ]);

        $result = (string) $attributes;

        self::assertStringContainsString('data-count="42"', $result);
        self::assertStringContainsString('tabindex="0"', $result);
    }

    /** @test */
    public function it_handles_empty_attributes(): void
    {
        $attributes = new Attributes([]);

        self::assertSame('', (string) $attributes);
        self::assertCount(0, $attributes);
        self::assertEmpty($attributes->all());
        self::assertFalse($attributes->has('anything'));
    }

    /** @test */
    public function it_chains_filtering_operations(): void
    {
        $attributes = new Attributes([
            'class' => 'btn btn-primary',
            'id' => 'submit-button',
            'data-id' => '123',
            'data-value' => '456',
        ]);

        $result = $attributes
            ->without('data-value')
            ->remove('id')
            ->only('class', 'data-id');

        self::assertCount(2, $result);
        self::assertTrue($result->has('class'));
        self::assertTrue($result->has('data-id'));
        self::assertFalse($result->has('id'));
        self::assertFalse($result->has('data-value'));
    }
}
