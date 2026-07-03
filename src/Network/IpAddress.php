<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidIpAddressException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * An IP address of either version 4 or version 6.
 */
final class IpAddress extends AbstractStringValueObject
{
    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    /**
     * The IP protocol version of this address.
     *
     * @return 4|6
     */
    public function version(): int
    {
        return \filter_var($this->value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) !== false ? 4 : 6;
    }

    protected function parse(string $value): string
    {
        if (\filter_var($value, \FILTER_VALIDATE_IP) === false) {
            throw new InvalidIpAddressException(\sprintf('Value "%s" is not a valid IP address.', $value));
        }

        return $value;
    }
}
