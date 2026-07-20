<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidIpv4Exception;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Support\ClassifiesIpAddress;

/**
 * An IPv4 address in dotted-decimal notation (e.g. 192.168.0.1).
 */
final class Ipv4Address extends AbstractStringValueObject
{
    use ClassifiesIpAddress;

    /**
     * RFC 1918 private ranges.
     */
    public function isPrivate(): bool
    {
        return $this->inRange('10.0.0.0/8')
            || $this->inRange('172.16.0.0/12')
            || $this->inRange('192.168.0.0/16');
    }

    /**
     * The 127.0.0.0/8 loopback range.
     */
    public function isLoopback(): bool
    {
        return $this->inRange('127.0.0.0/8');
    }

    /**
     * The 169.254.0.0/16 link-local (APIPA) range.
     */
    public function isLinkLocal(): bool
    {
        return $this->inRange('169.254.0.0/16');
    }

    /**
     * The 224.0.0.0/4 multicast range.
     */
    public function isMulticast(): bool
    {
        return $this->inRange('224.0.0.0/4');
    }

    protected function parse(string $value): string
    {
        if (\filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) === false) {
            throw new InvalidIpv4Exception(\sprintf('Value "%s" is not a valid IPv4 address.', $value));
        }

        return $value;
    }
}
