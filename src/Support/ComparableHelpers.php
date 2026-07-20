<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

use MSSTC4PHP\ValueObject\Contract\Comparable;

/**
 * Default comparison helpers derived from {@see Comparable::compareTo()}.
 * Mix into any value object that implements {@see Comparable} to gain the
 * ergonomic predicates and min/max selection without reimplementing them.
 */
trait ComparableHelpers
{
    abstract public function compareTo(Comparable $other): int;

    /**
     * @param static $other
     */
    public function isLessThan(Comparable $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * @param static $other
     */
    public function isGreaterThan(Comparable $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * @param static $other
     */
    public function isLessThanOrEqual(Comparable $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * @param static $other
     */
    public function isGreaterThanOrEqual(Comparable $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    /**
     * @param static $other
     */
    public function min(Comparable $other): static
    {
        return $this->compareTo($other) <= 0 ? $this : $other;
    }

    /**
     * @param static $other
     */
    public function max(Comparable $other): static
    {
        return $this->compareTo($other) >= 0 ? $this : $other;
    }
}
