<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidPercentageException;
use MSSTC4PHP\ValueObject\Exception\MissingDependencyException;
use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Support\AbstractFloatValueObject;

/**
 * A percentage within the inclusive range 0.0..100.0.
 *
 * Arithmetic delegates to {@see Decimal}, so it needs "brick/math" installed
 * and throws {@see MissingDependencyException}
 * when it is absent.
 */
final class Percentage extends AbstractFloatValueObject
{
    /**
     * Apply this percentage to a value (e.g. 20% applyTo 150 == 30).
     *
     * @throws MissingDependencyException when "brick/math" is not installed
     */
    public function applyTo(Decimal|int|string $value): Decimal
    {
        $base = $value instanceof Decimal ? $value : Decimal::from((string) $value);

        return $base->multiply((string) $this->value)->multiply('0.01');
    }

    /**
     * Reads as "20% of 150"; an alias of {@see applyTo()}.
     *
     * @throws MissingDependencyException when "brick/math" is not installed
     */
    public function of(Decimal|int|string $value): Decimal
    {
        return $this->applyTo($value);
    }

    /**
     * The percentage a part represents of a whole (e.g. fromRatio(1, 4) == 25).
     *
     * @throws MissingDependencyException when "brick/math" is not installed
     * @throws InvalidPercentageException when the ratio falls outside 0..100
     */
    public static function fromRatio(int|string $numerator, int|string $denominator): self
    {
        $ratio = Decimal::from((string) $numerator)->divide((string) $denominator, 10)->multiply('100');

        return self::from((float) $ratio->value);
    }

    protected function parse(float $value): float
    {
        if ($value < 0.0 || $value > 100.0) {
            throw new InvalidPercentageException(\sprintf('Percentage must be within 0..100, got %s.', $value));
        }

        return $value;
    }
}
