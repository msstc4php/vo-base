<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Exception;

/**
 * Thrown when a latitude is outside the -90..90 range.
 */
final class InvalidLatitudeException extends InvalidValueObjectException {}
