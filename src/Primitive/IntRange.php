<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Primitive;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidIntRangeException;

/**
 * An inclusive integer interval [min, max].
 */
final readonly class IntRange implements ValueObject
{
    public function __construct(
        public int $min,
        public int $max,
    ) {
        if ($min > $max) {
            throw new InvalidIntRangeException(\sprintf('Range minimum %d must not exceed maximum %d.', $min, $max));
        }
    }

    public function contains(int $value): bool
    {
        return $value >= $this->min && $value <= $this->max;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $other->min === $this->min
            && $other->max === $this->max;
    }

    /**
     * @return array{min: int, max: int}
     */
    public function jsonSerialize(): array
    {
        return ['min' => $this->min, 'max' => $this->max];
    }
}
