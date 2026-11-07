<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\InvalidIpRangeException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Support\PackedAddress;
use Override;

/**
 * An inclusive range of IP addresses of one version, "first-last"
 * (e.g. 10.0.0.5-10.0.0.20 or 2001:db8::1-2001:db8::ff). Unlike a {@see Cidr}
 * block it need not be aligned to a prefix boundary.
 *
 * Bounds are stored in RFC 5952 canonical form, while {@see IpAddress} and
 * {@see Cidr} keep their input spelling: compare addresses with contains(), not
 * with equals() against an address spelled differently (2001:DB8::1).
 */
final class IpRange extends AbstractStringValueObject
{
    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    public static function fromBounds(IpAddress $first, IpAddress $last): self
    {
        return self::from($first->value . '-' . $last->value);
    }

    public static function fromCidr(Cidr $cidr): self
    {
        return self::fromBounds($cidr->firstAddress(), $cidr->lastAddress());
    }

    /**
     * The lowest address, in canonical form.
     */
    public function firstAddress(): IpAddress
    {
        return IpAddress::fromString($this->bounds()[0]);
    }

    /**
     * The highest address, in canonical form.
     */
    public function lastAddress(): IpAddress
    {
        return IpAddress::fromString($this->bounds()[1]);
    }

    /**
     * @return 4|6
     */
    public function version(): int
    {
        return PackedAddress::byteLength($this->packedBounds()[0]) === 4 ? 4 : 6;
    }

    /**
     * Whether the address, or every address of the range, lies within this
     * range. Nothing of the other IP version is ever contained.
     */
    public function contains(IpAddress|self $value): bool
    {
        [$first, $last] = $this->packedBounds();
        [$otherFirst, $otherLast] = $value instanceof self
            ? $value->packedBounds()
            : [PackedAddress::pack($value->value), PackedAddress::pack($value->value)];

        return \strlen($first) === \strlen($otherFirst)
            && \strcmp($first, $otherFirst) <= 0
            && \strcmp($otherLast, $last) <= 0;
    }

    /**
     * Whether every address of this range lies within the other range.
     */
    public function isSubrangeOf(self $other): bool
    {
        return $other->contains($this);
    }

    /**
     * Whether the two ranges share at least one address. Ranges of different IP
     * versions never overlap.
     */
    public function overlaps(self $other): bool
    {
        [$first, $last] = $this->packedBounds();
        [$otherFirst, $otherLast] = $other->packedBounds();

        return \strlen($first) === \strlen($otherFirst)
            && \strcmp($first, $otherLast) <= 0
            && \strcmp($otherFirst, $last) <= 0;
    }

    /**
     * The addresses both ranges share, or null when they do not overlap.
     */
    public function intersect(self $other): ?self
    {
        if (!$this->overlaps($other)) {
            return null;
        }

        [$first, $last] = $this->packedBounds();
        [$otherFirst, $otherLast] = $other->packedBounds();

        // strcmp(), not max()/min(): those compare numeric-looking byte strings ("1e10") as numbers.
        return $this->fromPacked(
            \strcmp($first, $otherFirst) >= 0 ? $first : $otherFirst,
            \strcmp($last, $otherLast) <= 0 ? $last : $otherLast,
        );
    }

    /**
     * Whether one range ends exactly one address before the other starts, so
     * that together they form a single contiguous range.
     */
    public function isAdjacentTo(self $other): bool
    {
        [$first, $last] = $this->packedBounds();
        [$otherFirst, $otherLast] = $other->packedBounds();

        return \strlen($first) === \strlen($otherFirst)
            && (PackedAddress::increment($last) === $otherFirst || PackedAddress::increment($otherLast) === $first);
    }

    /**
     * The exact number of addresses in the range as a decimal string; IPv6
     * ranges routinely exceed PHP's integer range.
     *
     * @return numeric-string
     */
    public function addressCount(): string
    {
        [$first, $last] = $this->packedBounds();

        return PackedAddress::inclusiveCount($first, $last);
    }

    /**
     * The smallest list of CIDR blocks that covers exactly this range, in
     * ascending order and canonical form. At most 62 blocks for IPv4, 254 for IPv6.
     *
     * @return non-empty-list<Cidr>
     */
    public function toCidrs(): array
    {
        [$cursor, $last] = $this->packedBounds();
        $bytes = PackedAddress::byteLength($cursor);
        $totalBits = $bytes * 8;

        $masks = [];
        $blocks = [];
        while (true) {
            // Widest block that starts aligned at the cursor and does not run past the end.
            $hostBits = \min($totalBits, PackedAddress::trailingZeroBits($cursor));
            $blockLast = $cursor | ($masks[$hostBits] ??= PackedAddress::lowBitsMask($bytes, $hostBits));
            while (\strcmp($blockLast, $last) > 0) {
                $hostBits = \max(0, $hostBits - 1);
                $blockLast = $cursor | ($masks[$hostBits] ??= PackedAddress::lowBitsMask($bytes, $hostBits));
            }

            $blocks[] = Cidr::from(\sprintf('%s/%d', PackedAddress::unpack($cursor), $totalBits - $hostBits));

            if ($blockLast === $last) {
                return $blocks;
            }

            $cursor = PackedAddress::increment($blockLast)
                ?? throw new LogicException('Unreachable: a block below the range end has a successor.');
        }
    }

    #[Override]
    protected function parse(string $value): string
    {
        $parts = \explode('-', $value);
        if (\count($parts) !== 2) {
            throw new InvalidIpRangeException(\sprintf('Value "%s" is not a valid IP range "first-last".', InvalidIpRangeException::excerpt($value)));
        }

        [$first, $last] = $parts;
        if (\filter_var($first, \FILTER_VALIDATE_IP) === false || \filter_var($last, \FILTER_VALIDATE_IP) === false) {
            throw new InvalidIpRangeException(\sprintf('IP range "%s" has an invalid bound.', InvalidIpRangeException::excerpt($value)));
        }

        $packedFirst = PackedAddress::pack($first);
        $packedLast = PackedAddress::pack($last);

        if (\strlen($packedFirst) !== \strlen($packedLast)) {
            throw new InvalidIpRangeException(\sprintf('IP range "%s" mixes IPv4 and IPv6.', InvalidIpRangeException::excerpt($value)));
        }

        if (\strcmp($packedFirst, $packedLast) > 0) {
            throw new InvalidIpRangeException(\sprintf('IP range "%s" starts after it ends.', InvalidIpRangeException::excerpt($value)));
        }

        return PackedAddress::unpack($packedFirst) . '-' . PackedAddress::unpack($packedLast);
    }

    private function fromPacked(string $first, string $last): self
    {
        return self::from(PackedAddress::unpack($first) . '-' . PackedAddress::unpack($last));
    }

    /**
     * @return array{non-empty-string, non-empty-string}
     */
    private function bounds(): array
    {
        $separator = (int) \strpos($this->value, '-');
        $first = \substr($this->value, 0, $separator);
        $last = \substr($this->value, $separator + 1);
        if ($first === '' || $last === '') {
            throw new LogicException(\sprintf('Unreachable: "%s" was validated at construction.', $this->value));
        }

        return [$first, $last];
    }

    /**
     * @return array{non-empty-string, non-empty-string}
     */
    private function packedBounds(): array
    {
        [$first, $last] = $this->bounds();

        return [PackedAddress::pack($first), PackedAddress::pack($last)];
    }
}
