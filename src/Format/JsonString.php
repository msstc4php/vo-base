<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidJsonStringException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * A syntactically valid JSON document, kept in its original textual form.
 */
final class JsonString extends AbstractStringValueObject
{
    #[Override]
    protected function parse(string $value): string
    {
        if (!\json_validate($value)) {
            throw new InvalidJsonStringException('Invalid JSON string.');
        }

        return $value;
    }
}
