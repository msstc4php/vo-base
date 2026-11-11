<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Exception;

/**
 * A stored or transmitted payload has the wrong type or shape for the value
 * object it should become. Extends the general exception so a bridge that turns
 * invalid input into a validation failure catches both cases with one clause.
 */
final class InvalidPayloadException extends InvalidValueObjectException
{
}
