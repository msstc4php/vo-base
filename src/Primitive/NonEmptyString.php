<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidNonEmptyStringException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * A string that is not blank once trimmed. The original value is preserved
 * verbatim; only emptiness is rejected.
 */
final class NonEmptyString extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        if (\trim($value) === '') {
            throw new InvalidNonEmptyStringException('Value must not be empty.');
        }

        return $value;
    }
}
