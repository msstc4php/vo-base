<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidIpv6Exception;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * An IPv6 address (e.g. 2001:db8::1).
 */
final class Ipv6Address extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        if (\filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) === false) {
            throw new InvalidIpv6Exception(\sprintf('Value "%s" is not a valid IPv6 address.', $value));
        }

        return $value;
    }
}
