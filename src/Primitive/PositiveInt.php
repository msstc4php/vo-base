<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidPositiveIntException;
use MSSTC4PHP\ValueObject\Support\AbstractIntValueObject;

/**
 * An integer strictly greater than zero.
 */
final class PositiveInt extends AbstractIntValueObject
{
    protected function parse(int $value): int
    {
        if ($value <= 0) {
            throw new InvalidPositiveIntException(\sprintf('Value must be positive, got %d.', $value));
        }

        return $value;
    }
}
