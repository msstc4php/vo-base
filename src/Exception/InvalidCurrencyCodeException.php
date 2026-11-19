<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Exception;

/**
 * Thrown when a value is not a known ISO 4217 currency code.
 */
final class InvalidCurrencyCodeException extends InvalidValueObjectException
{
}
