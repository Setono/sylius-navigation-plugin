<?php

declare(strict_types=1);

namespace Setono\SyliusNavigationPlugin\Twig;

/**
 * Much of this code has been copied from \Symfony\UX\TwigComponent\ComponentAttributes
 *
 * @implements \IteratorAggregate<string, scalar|\Stringable>
 */
final class Attributes implements \Stringable, \IteratorAggregate, \Countable
{
    /** @var array<string, true> */
    private array $rendered = [];

    /**
     * @param array<string, scalar|\Stringable> $attributes
     */
    public function __construct(private array $attributes)
    {
    }

    public function __toString(): string
    {
        $attributes = '';

        foreach ($this->attributes as $key => $value) {
            if (isset($this->rendered[$key])) {
                continue;
            }

            $attributes .= match ($value) {
                true => ' ' . $key,
                false => '',
                default => \sprintf(' %s="%s"', $key, $value),
            };

            $this->rendered[$key] = true;
        }

        return $attributes;
    }

    public function __clone()
    {
        $this->rendered = [];
    }

    public function render(string $attribute): ?string
    {
        if (!$this->has($attribute)) {
            return null;
        }

        $value = $this->attributes[$attribute];

        if ($value instanceof \Stringable) {
            $value = (string) $value;
        }

        if (true === $value && str_starts_with($attribute, 'aria-')) {
            $value = 'true';
        }

        if (!\is_string($value)) {
            throw new \LogicException(\sprintf('Can only get string attributes (%s is a "%s").', $attribute, get_debug_type($value)));
        }

        $this->rendered[$attribute] = true;

        return $value;
    }

    /**
     * @return array<string, scalar|\Stringable>
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Extract only these attributes.
     */
    public function only(string ...$attributes): self
    {
        $newAttributes = [];

        foreach ($this->attributes as $attribute => $value) {
            if (\in_array($attribute, $attributes, true)) {
                $newAttributes[$attribute] = $value;
            }
        }

        return new self($newAttributes);
    }

    /**
     * Extract all but these attributes.
     */
    public function without(string ...$attributes): self
    {
        $clone = clone $this;

        foreach ($attributes as $attribute) {
            unset($clone->attributes[$attribute]);
        }

        return $clone;
    }

    public function remove(string $attribute): self
    {
        $attributes = $this->attributes;

        unset($attributes[$attribute]);

        return new self($attributes);
    }

    /**
     * @return \ArrayIterator<string, scalar|\Stringable>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->attributes);
    }

    /**
     * @phpstan-assert-if-true scalar|\Stringable $this->attributes[$attribute]
     */
    public function has(string $attribute): bool
    {
        return \array_key_exists($attribute, $this->attributes);
    }

    public function count(): int
    {
        return \count($this->attributes);
    }
}
