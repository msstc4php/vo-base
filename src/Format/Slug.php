<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidSlugException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * URL slug: lowercase alphanumeric words separated by single hyphens.
 */
final class Slug extends AbstractStringValueObject
{
    #[Override]
    protected function parse(string $value): string
    {
        if (\preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value) !== 1) {
            throw new InvalidSlugException(\sprintf('Invalid slug: "%s".', InvalidSlugException::excerpt($value)));
        }

        return $value;
    }
}
