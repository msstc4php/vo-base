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

    /**
     * The number of integers in the inclusive interval.
     *
     * @return positive-int
     */
    public function length(): int
    {
        // Always >= 1: the constructor guarantees min <= max.
        return max(1, $this->max - $this->min + 1);
    }

    public function overlaps(self $other): bool
    {
        return $this->min <= $other->max && $other->min <= $this->max;
    }

    /**
     * The overlapping interval shared with the other, or null when disjoint.
     */
    public function intersect(self $other): ?self
    {
        if (!$this->overlaps($other)) {
            return null;
        }

        return new self(
            max($this->min, $other->min),
            min($this->max, $other->max),
        );
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
