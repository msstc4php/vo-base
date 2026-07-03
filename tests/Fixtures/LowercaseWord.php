<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Fixtures;

use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * Test fixture: a non-empty word, normalized to lowercase. Exercises the
 * string base's validate-and-normalize hook.
 */
final class LowercaseWord extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        $trimmed = \trim($value);

        if ($trimmed === '') {
            throw new InvalidValueObjectException('Word must not be empty.');
        }

        return \strtolower($trimmed);
    }
}
