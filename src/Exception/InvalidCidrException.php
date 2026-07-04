<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Exception;

/**
 * Thrown when a value is not a valid CIDR block (address/prefix).
 */
final class InvalidCidrException extends InvalidValueObjectException
{
}
