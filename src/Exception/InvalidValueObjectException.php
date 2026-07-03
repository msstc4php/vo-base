<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Exception;

use InvalidArgumentException;

/**
 * Base type for every value-object construction failure. Each value object
 * throws its own subclass so callers can catch a specific kind or the whole family.
 */
class InvalidValueObjectException extends InvalidArgumentException {}
