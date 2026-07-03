<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Geo;

use MSSTC4PHP\ValueObject\Exception\InvalidLatitudeException;
use MSSTC4PHP\ValueObject\Support\AbstractFloatValueObject;

/**
 * Geographic latitude in decimal degrees, bounded to -90..90 inclusive.
 */
final class Latitude extends AbstractFloatValueObject
{
    protected function parse(float $value): float
    {
        if ($value < -90.0 || $value > 90.0) {
            throw new InvalidLatitudeException(\sprintf('Latitude must be within -90..90, got %s.', $value));
        }

        return $value;
    }
}
