<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

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

    abstract public function isReserved(): bool;

    /**
     * A routable public address: none of the private, loopback, link-local,
     * multicast, or reserved/special-purpose ranges.
     *
     * WARNING: this is a syntactic classification, not a security boundary. Do
     * NOT use it as an SSRF allow/deny gate — it does not resolve names, cannot
     * see DNS-rebinding, and may lag the IANA special-purpose registries. Use a
     * dedicated egress-filtering layer for SSRF defense.
     */
    public function isPublic(): bool
    {
        return !$this->isPrivate()
            && !$this->isLoopback()
            && !$this->isLinkLocal()
            && !$this->isMulticast()
            && !$this->isReserved();
    }

    public function isInRange(Cidr $cidr): bool
    {
        return $cidr->contains(IpAddress::fromString($this->value));
    }

    /**
     * Each call constructs a fresh Cidr and IpAddress; the range constants are
     * deliberately not memoized — the cost is negligible for a value-object
     * library, and caching would trade that for shared mutable state.
     */
    private function inRange(string $cidr): bool
    {
        return Cidr::from($cidr)->contains(IpAddress::fromString($this->value));
    }
}
