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

    /**
     * IANA special-purpose ranges that are not globally routable: "this host"
     * (0.0.0.0/8), CGNAT (100.64.0.0/10), IETF protocol assignments
     * (192.0.0.0/24), the documentation ranges (192.0.2.0/24, 198.51.100.0/24,
     * 203.0.113.0/24), benchmarking (198.18.0.0/15), and reserved/future use
     * plus limited broadcast (240.0.0.0/4, which includes 255.255.255.255).
     */
    public function isReserved(): bool
    {
        return $this->inRange('0.0.0.0/8')
            || $this->inRange('100.64.0.0/10')
            || $this->inRange('192.0.0.0/24')
            || $this->inRange('192.0.2.0/24')
            || $this->inRange('198.18.0.0/15')
            || $this->inRange('198.51.100.0/24')
            || $this->inRange('203.0.113.0/24')
            || $this->inRange('240.0.0.0/4');
    }

    protected function parse(string $value): string
    {
        if (\filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) === false) {
            throw new InvalidIpv4Exception(\sprintf('Value "%s" is not a valid IPv4 address.', $value));
        }

        return $value;
    }
}
