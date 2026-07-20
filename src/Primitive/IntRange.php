<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Primitive;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidIntRangeException;
use RangeException;

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
     *
     * @throws RangeException when the count exceeds PHP's integer range
     */
    public function length(): int
    {
        // Guard the span before computing it, so the subtraction itself never
        // overflows into a float. PHP_INT_MAX + $this->min is safe here because
        // $this->min is negative in that branch.
        if ($this->min < 0 && $this->max > \PHP_INT_MAX + $this->min) {
            throw new RangeException(\sprintf('Length of range [%d, %d] exceeds the integer range.', $this->min, $this->max));
        }

        $span = $this->max - $this->min;
        if ($span === \PHP_INT_MAX) {
            // span + 1 would overflow.
            throw new RangeException(\sprintf('Length of range [%d, %d] exceeds the integer range.', $this->min, $this->max));
        }

        // max(1, …) only satisfies the positive-int type; the constructor's
        // min <= max invariant already makes $span + 1 >= 1.
        return max(1, $span + 1);
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
