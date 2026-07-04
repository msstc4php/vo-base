<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidCidrException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

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
}
