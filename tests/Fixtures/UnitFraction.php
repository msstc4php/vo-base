<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Fixtures;

use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\AbstractFloatValueObject;
use Override;

/**
 * Test fixture: a float bounded to 0.0..1.0 inclusive.
 */
final class UnitFraction extends AbstractFloatValueObject
{
    #[Override]
    protected function parse(float $value): float
    {
        if ($value < 0.0 || $value > 1.0) {
            throw new InvalidValueObjectException(\sprintf('Fraction must be within 0..1, got %s.', $value));
        }

        return $value;
    }
}
