<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidHexColorException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * CSS hex color in #RGB or #RRGGBB form, normalized to lowercase.
 */
final class HexColor extends AbstractStringValueObject
{
    #[Override]
    protected function parse(string $value): string
    {
        if (\preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/D', $value) !== 1) {
            throw new InvalidHexColorException(\sprintf('Invalid hex color: "%s".', InvalidHexColorException::excerpt($value)));
        }

        return \strtolower($value);
    }
}
