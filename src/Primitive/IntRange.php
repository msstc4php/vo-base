<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Primitive;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidIntRangeException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\FreshInstance;
use Override;
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
        $this->check($min, $max);
    }

    /**
     * @return array{min: int, max: int}
     */
    public function __serialize(): array
    {
        return ['min' => $this->min, 'max' => $this->max];
    }

    /**
     * Runs the constructor's checks again, so a forged or stale payload never yields an instance the constructor
     * would refuse. Other keys are ignored.
     *
     * @param array<array-key, mixed> $data untrusted: whatever the serialized string holds
     *
     * @throws InvalidValueObjectException when a key is missing or of the wrong type, or the values break the type's rules
     * @throws LogicException when called on an object that already holds a value
     */
    public function __unserialize(array $data): void
    {
        FreshInstance::assert($this, 'min');

        $min = $data['min'] ?? null;
        $max = $data['max'] ?? null;
        if (!\is_int($min) || !\is_int($max)) {
            throw new InvalidValueObjectException(\sprintf('A serialized %s must hold min and max as ints.', self::class));
        }

        $this->check($min, $max);
        $this->min = $min;
        $this->max = $max;
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

    #[Override]
    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $other->min === $this->min
            && $other->max === $this->max;
    }

    /**
     * @return array{min: int, max: int}
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return ['min' => $this->min, 'max' => $this->max];
    }

    /**
     * @throws InvalidIntRangeException
     */
    private function check(int $min, int $max): void
    {
        if ($min > $max) {
            throw new InvalidIntRangeException(\sprintf('Range minimum %d must not exceed maximum %d.', $min, $max));
        }
    }
}
