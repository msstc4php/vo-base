<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidPercentageException;
use MSSTC4PHP\ValueObject\Support\AbstractFloatValueObject;

/**
 * A percentage within the inclusive range 0.0..100.0.
 */
final class Percentage extends AbstractFloatValueObject
{
    protected function parse(float $value): float
    {
        if ($value < 0.0 || $value > 100.0) {
            throw new InvalidPercentageException(\sprintf('Percentage must be within 0..100, got %s.', $value));
        }

        return $value;
    }
}
