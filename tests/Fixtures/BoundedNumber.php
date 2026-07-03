<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Fixtures;

use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\AbstractIntValueObject;

/**
 * Test fixture: an integer bounded to 0..10 inclusive. Exercises the int base's
 * range hook and the comparison contract.
 */
final class BoundedNumber extends AbstractIntValueObject
{
    protected function parse(int $value): int
    {
        if ($value < 0 || $value > 10) {
            throw new InvalidValueObjectException(\sprintf('Number must be within 0..10, got %d.', $value));
        }

        return $value;
    }
}
