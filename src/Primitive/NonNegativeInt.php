<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidNonNegativeIntException;
use MSSTC4PHP\ValueObject\Support\AbstractIntValueObject;
use Override;

/**
 * An integer greater than or equal to zero.
 */
final class NonNegativeInt extends AbstractIntValueObject
{
    #[Override]
    protected function parse(int $value): int
    {
        if ($value < 0) {
            throw new InvalidNonNegativeIntException(\sprintf('Value must not be negative, got %d.', $value));
        }

        return $value;
    }
}
