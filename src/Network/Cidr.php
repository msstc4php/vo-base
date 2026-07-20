<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\InvalidCidrException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use RangeException;

/**
 * A CIDR block in "address/prefix" notation (e.g. 192.168.0.0/24 or 2001:db8::/32).
 */
final class Cidr extends AbstractStringValueObject
{
    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    /**
     * The network address part as an IP address value object.
     */
    public function address(): IpAddress
    {
        [$address] = explode('/', $this->value, 2);

        return IpAddress::fromString($address);
    }

    /**
     * The prefix length (number of leading network bits).
     *
     * @return int<0, 128>
     */
    public function prefixLength(): int
    {
        [, $prefix] = explode('/', $this->value, 2);

        // Bounded 0..128 by parse(); clamp so the static type matches the invariant.
        return max(0, min(128, (int) $prefix));
    }

    /**
     * The IP protocol version of the network address.
     *
     * @return 4|6
     */
    public function version(): int
    {
        [$address] = explode('/', $this->value, 2);

        return filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) !== false ? 4 : 6;
    }

    /**
     * Whether the given address falls within this block. An address of the
     * other IP version is never contained.
     */
    public function contains(IpAddress $address): bool
    {
        $candidate = @inet_pton($address->value);
        $network = $this->packedNetwork();
        if ($candidate === false || \strlen($candidate) !== \strlen($network)) {
            return false;
        }

        return ($candidate & $this->maskBytes()) === $network;
    }

    /**
     * The network address (all host bits cleared).
     */
    public function firstAddress(): IpAddress
    {
        return $this->addressFromPacked($this->packedNetwork());
    }

    /**
     * The last address of the block (all host bits set). For IPv4 this is the
     * broadcast address; for IPv6 it is simply the highest address in the range.
     */
    public function lastAddress(): IpAddress
    {
        return $this->addressFromPacked($this->packedNetwork() | ~$this->maskBytes());
    }

    /**
     * The subnet mask as an address (e.g. 255.255.255.0 for a /24).
     */
    public function netmask(): IpAddress
    {
        return $this->addressFromPacked($this->maskBytes());
    }

    /**
     * The IPv4 broadcast address, or null for IPv6 (which has no broadcast).
     */
    public function broadcast(): ?IpAddress
    {
        return $this->version() === 4 ? $this->lastAddress() : null;
    }

    /**
     * The number of usable host addresses in the block. Blocks wider than two
     * addresses exclude the network and broadcast addresses.
     *
     * @return positive-int
     *
     * @throws RangeException when the count exceeds PHP's integer range (very wide IPv6 blocks)
     */
    public function hostCount(): int
    {
        $hostBits = (\strlen($this->packedNetwork()) * 8) - $this->prefixLength();
        if ($hostBits >= 63) {
            throw new RangeException(\sprintf('Host count of "%s" exceeds the integer range.', $this->value));
        }

        $total = max(1, 1 << $hostBits);

        return $total > 2 ? $total - 2 : $total;
    }

    /**
     * Whether this block and the other share at least one address. Blocks of
     * different IP versions never overlap.
     */
    public function overlaps(self $other): bool
    {
        if ($this->version() !== $other->version()) {
            return false;
        }

        return \strcmp($this->packedNetwork(), $other->packedLast()) <= 0
            && \strcmp($other->packedNetwork(), $this->packedLast()) <= 0;
    }

    /**
     * Whether this block is fully contained within the other block.
     */
    public function isSubnetOf(self $other): bool
    {
        if ($this->version() !== $other->version()) {
            return false;
        }

        return \strcmp($other->packedNetwork(), $this->packedNetwork()) <= 0
            && \strcmp($this->packedLast(), $other->packedLast()) <= 0;
    }

    protected function parse(string $value): string
    {
        $parts = explode('/', $value);
        if (\count($parts) !== 2) {
            throw new InvalidCidrException(sprintf('Value "%s" is not a valid CIDR block.', $value));
        }

        [$address, $prefix] = $parts;

        $isIpv4 = filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) !== false;
        $isIpv6 = filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) !== false;
        if (!$isIpv4 && !$isIpv6) {
            throw new InvalidCidrException(sprintf('CIDR "%s" has an invalid network address.', $value));
        }

        if ($prefix === '' || (string) (int) $prefix !== $prefix) {
            throw new InvalidCidrException(sprintf('CIDR "%s" has a non-integer prefix.', $value));
        }

        $prefixLength = (int) $prefix;
        $maxPrefix = $isIpv4 ? 32 : 128;
        if ($prefixLength < 0 || $prefixLength > $maxPrefix) {
            throw new InvalidCidrException(sprintf('CIDR "%s" prefix is out of the 0..%d range.', $value, $maxPrefix));
        }

        return sprintf('%s/%d', $address, $prefixLength);
    }

    /**
     * The network address as a packed in-addr binary string (4 or 16 bytes).
     */
    private function packedNetwork(): string
    {
        [$address] = explode('/', $this->value, 2);
        $packed = inet_pton($address);
        if ($packed === false) {
            // Unreachable: the address part was validated at construction.
            throw new LogicException(\sprintf('Cannot pack address of "%s".', $this->value));
        }

        return $packed & $this->maskBytes();
    }

    /**
     * The last (highest) address of the block as a packed binary string.
     */
    private function packedLast(): string
    {
        return $this->packedNetwork() | ~$this->maskBytes();
    }

    /**
     * The prefix mask as a packed binary string of the address's byte length.
     */
    private function maskBytes(): string
    {
        $totalBytes = $this->version() === 4 ? 4 : 16;
        $prefix = $this->prefixLength();

        $mask = '';
        for ($i = 0; $i < $totalBytes; $i++) {
            $bits = min(8, max(0, $prefix - $i * 8));
            $mask .= chr($bits === 0 ? 0 : (0xFF << (8 - $bits)) & 0xFF);
        }

        return $mask;
    }

    private function addressFromPacked(string $packed): IpAddress
    {
        $address = inet_ntop($packed);
        if ($address === false) {
            // Unreachable: $packed is always a valid 4- or 16-byte string.
            throw new LogicException('Cannot render a packed address.');
        }

        return IpAddress::fromString($address);
    }
}
