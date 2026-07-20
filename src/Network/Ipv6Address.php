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
    use ClassifiesIpAddress;

    /**
     * The fc00::/7 unique-local range (the IPv6 analogue of private addresses).
     */
    public function isPrivate(): bool
    {
        return $this->inRange('fc00::/7');
    }

    /**
     * The ::1/128 loopback address.
     */
    public function isLoopback(): bool
    {
        return $this->inRange('::1/128');
    }

    /**
     * The fe80::/10 link-local range.
     */
    public function isLinkLocal(): bool
    {
        return $this->inRange('fe80::/10');
    }

    /**
     * The ff00::/8 multicast range.
     */
    public function isMulticast(): bool
    {
        return $this->inRange('ff00::/8');
    }

    /**
     * IANA special-purpose ranges that are not globally routable: the
     * unspecified address (::/128), IPv4-mapped (::ffff:0:0/96), IPv4/IPv6
     * translation (64:ff9b::/96), and the documentation range (2001:db8::/32).
     */
    public function isReserved(): bool
    {
        return $this->inRange('::/128')
            || $this->inRange('::ffff:0:0/96')
            || $this->inRange('64:ff9b::/96')
            || $this->inRange('2001:db8::/32');
    }

    protected function parse(string $value): string
    {
        if (\filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) === false) {
            throw new InvalidIpv6Exception(\sprintf('Value "%s" is not a valid IPv6 address.', $value));
        }

        return $value;
    }
}
