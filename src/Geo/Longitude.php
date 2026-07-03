<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Geo;

use MSSTC4PHP\ValueObject\Exception\InvalidLongitudeException;
use MSSTC4PHP\ValueObject\Support\AbstractFloatValueObject;

/**
 * Geographic longitude in decimal degrees, bounded to -180..180 inclusive.
 */
final class Longitude extends AbstractFloatValueObject
{
    protected function parse(float $value): float
    {
        if ($value < -180.0 || $value > 180.0) {
            throw new InvalidLongitudeException(\sprintf('Longitude must be within -180..180, got %s.', $value));
        }

        return $value;
    }
}
