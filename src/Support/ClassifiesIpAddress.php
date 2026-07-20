<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

use MSSTC4PHP\ValueObject\Network\Cidr;
use MSSTC4PHP\ValueObject\Network\IpAddress;

/**
 * Shared IP classification behavior. The concrete address type provides the
 * family-specific range predicates; this trait derives {@see isPublic()} and
 * the CIDR-membership helpers from them and the stored value.
 */
trait ClassifiesIpAddress
{
    abstract public function isPrivate(): bool;

    abstract public function isLoopback(): bool;

    abstract public function isLinkLocal(): bool;

    abstract public function isMulticast(): bool;

    /**
     * A routable public address: none of the private, loopback, link-local, or
     * multicast ranges.
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

    private function inRange(string $cidr): bool
    {
        return Cidr::from($cidr)->contains(IpAddress::fromString($this->value));
    }
}
