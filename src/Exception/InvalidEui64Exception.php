<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Exception;

/**
 * Thrown when a value is not a valid 64-bit EUI-64 identifier.
 */
final class InvalidEui64Exception extends InvalidValueObjectException
{
}
