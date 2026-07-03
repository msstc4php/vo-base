<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidIpv4Exception;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * An IPv4 address in dotted-decimal notation (e.g. 192.168.0.1).
 */
final class Ipv4Address extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        if (\filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) === false) {
            throw new InvalidIpv4Exception(\sprintf('Value "%s" is not a valid IPv4 address.', $value));
        }

        return $value;
    }
}
