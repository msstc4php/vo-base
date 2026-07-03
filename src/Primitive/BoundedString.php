<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Primitive;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidBoundedStringException;
use Stringable;

/**
 * A string whose length (in Unicode characters) is constrained to per-instance
 * bounds. Because the bounds are part of the value, this cannot use the shared
 * string base and carries its own construction and equality logic.
 */
final readonly class BoundedString implements ValueObject, Stringable
{
    public function __construct(
        public string $value,
        public int $minLength = 0,
        public ?int $maxLength = null,
    ) {
        if ($minLength < 0) {
            throw new InvalidBoundedStringException(\sprintf('Minimum length must not be negative, got %d.', $minLength));
        }

        if ($maxLength !== null && $maxLength < $minLength) {
            throw new InvalidBoundedStringException(\sprintf('Maximum length %d must not be less than minimum length %d.', $maxLength, $minLength));
        }

        $length = \mb_strlen($value);
        $upperBound = $maxLength ?? \PHP_INT_MAX;

        if ($length < $minLength || $length > $upperBound) {
            throw new InvalidBoundedStringException(\sprintf('Length %d is outside the allowed range %d..%s.', $length, $minLength, $maxLength ?? 'unbounded'));
        }
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $other->value === $this->value
            && $other->minLength === $this->minLength
            && $other->maxLength === $this->maxLength;
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
