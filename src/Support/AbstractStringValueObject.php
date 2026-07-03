<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;

/**
 * Base for string-backed value objects. Subclasses implement {@see parse()} to
 * validate the raw input and return the canonical stored form; every other
 * concern (construction convention, equality, serialization) is provided here.
 */
abstract class AbstractStringValueObject implements StringConvertible, ValueObject
{
    public readonly string $value;

    final public function __construct(string $value)
    {
        $this->value = $this->parse($value);
    }

    /**
     * Validate the raw input and return the canonical form to store.
     *
     * @throws InvalidValueObjectException when the input is not a valid value
     */
    abstract protected function parse(string $value): string;

    public static function from(string $value): static
    {
        return new static($value);
    }

    public static function tryFrom(string $value): ?static
    {
        try {
            return new static($value);
        } catch (InvalidValueObjectException) {
            return null;
        }
    }

    public static function isValid(string $value): bool
    {
        return static::tryFrom($value) instanceof static;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof static && $other->value === $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
