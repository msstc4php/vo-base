<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

use MSSTC4PHP\ValueObject\Contract\Comparable;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use Stringable;

/**
 * Base for float-backed value objects with a natural ordering.
 */
abstract class AbstractFloatValueObject implements Comparable, ValueObject, Stringable
{
    public readonly float $value;

    final public function __construct(float $value)
    {
        $this->value = $this->parse($value);
    }

    /**
     * @throws InvalidValueObjectException when the input is out of range
     */
    abstract protected function parse(float $value): float;

    public static function from(float $value): static
    {
        return new static($value);
    }

    public static function tryFrom(float $value): ?static
    {
        try {
            return new static($value);
        } catch (InvalidValueObjectException) {
            return null;
        }
    }

    public static function isValid(float $value): bool
    {
        return static::tryFrom($value) instanceof static;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof static && $other->value === $this->value;
    }

    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof static) {
            throw new InvalidValueObjectException(\sprintf('Cannot compare %s with %s.', static::class, $other::class));
        }

        return $this->value <=> $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function jsonSerialize(): float
    {
        return $this->value;
    }
}
