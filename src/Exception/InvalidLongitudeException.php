<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Exception;

/**
 * Thrown when a longitude is outside the -180..180 range.
 */
final class InvalidLongitudeException extends InvalidValueObjectException
{
}
