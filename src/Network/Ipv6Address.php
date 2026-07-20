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
     * A routable public address: none of the unique-local, loopback,
     * link-local, or multicast ranges.
     */
    public function isPublic(): bool
    {
        return !$this->isPrivate()
            && !$this->isLoopback()
            && !$this->isLinkLocal()
            && !$this->isMulticast();
    }

    public function isInRange(Cidr $cidr): bool
    {
        return $cidr->contains(IpAddress::fromString($this->value));
    }

    protected function parse(string $value): string
    {
        if (\filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) === false) {
            throw new InvalidIpv6Exception(\sprintf('Value "%s" is not a valid IPv6 address.', $value));
        }

        return $value;
    }

    private function inRange(string $cidr): bool
    {
        return Cidr::from($cidr)->contains(IpAddress::fromString($this->value));
    }
}
