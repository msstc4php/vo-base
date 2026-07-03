<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Exception;

/**
 * Thrown when a value is neither a valid IPv4 nor IPv6 address.
 */
final class InvalidIpAddressException extends InvalidValueObjectException {}
