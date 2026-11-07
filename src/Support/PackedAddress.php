<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

use LogicException;

/**
 * Arithmetic on packed (inet_pton) addresses as unsigned big-endian integers of
 * 4 or 16 bytes. Pure byte arithmetic: IPv6 counts exceed PHP's integer range and
 * the core takes no bignum dependency.
 *
 * @internal
 */
final class PackedAddress
{
    /**
     * @return non-empty-string
     */
    public static function pack(string $address): string
    {
        $packed = @\inet_pton($address);
        if ($packed === false || $packed === '') {
            throw new LogicException(\sprintf('Cannot pack address "%s".', $address));
        }

        return $packed;
    }

    /**
     * RFC 5952 text form. Not inet_ntop(): glibc prints addresses in ::/96 with a
     * dotted tail (::1.2.3.4) where musl and macOS print ::102:304, so a stored
     * canonical form would differ between platforms.
     *
     * @return non-empty-string
     */
    public static function unpack(string $packed): string
    {
        $text = self::render($packed);
        if ($text === '') {
            throw new LogicException('Cannot render a packed address.');
        }

        return $text;
    }

    /**
     * @return 4|16
     */
    public static function byteLength(string $packed): int
    {
        return match (\strlen($packed)) {
            4 => 4,
            16 => 16,
            default => throw new LogicException('A packed address is 4 or 16 bytes long.'),
        };
    }

    /**
     * A mask of the given byte length with the lowest $bits bits set.
     *
     * @param 4|16 $bytes
     * @param int<0, 128> $bits
     */
    public static function lowBitsMask(int $bytes, int $bits): string
    {
        $mask = '';
        for ($i = $bytes - 1; $i >= 0; $i--) {
            $set = \min(8, \max(0, $bits - $i * 8));
            $mask .= \chr($set === 0 ? 0 : (0xFF >> (8 - $set)));
        }

        return $mask;
    }

    /**
     * @return int<0, 128>
     */
    public static function trailingZeroBits(string $packed): int
    {
        $count = 0;
        for ($i = self::byteLength($packed) - 1; $i >= 0; $i--) {
            $byte = \ord($packed[$i]);
            if ($byte === 0) {
                $count += 8;

                continue;
            }

            while (($byte & 1) === 0) {
                $byte >>= 1;
                $count++;
            }

            break;
        }

        return \min(128, $count);
    }

    /**
     * The next address, or null past the top of the address space.
     */
    public static function increment(string $packed): ?string
    {
        for ($i = \strlen($packed) - 1; $i >= 0; $i--) {
            $byte = \ord($packed[$i]);
            if ($byte < 0xFF) {
                $packed[$i] = \chr($byte + 1);

                return $packed;
            }

            $packed[$i] = "\x00";
        }

        return null;
    }

    /**
     * The number of addresses from $first to $last inclusive, as a decimal string.
     *
     * @return numeric-string
     */
    public static function inclusiveCount(string $first, string $last): string
    {
        if (\strlen($first) !== \strlen($last) || \strcmp($first, $last) > 0) {
            throw new LogicException('An inclusive count needs two addresses of one version, first <= last.');
        }

        $difference = '';
        $borrow = 0;
        for ($i = \strlen($last) - 1; $i >= 0; $i--) {
            $digit = \ord($last[$i]) - \ord($first[$i]) - $borrow;
            $borrow = $digit < 0 ? 1 : 0;
            // chr() keeps the low 8 bits, which is the borrowed digit when $digit is negative.
            $difference = \chr($digit) . $difference;
        }

        return self::toDecimalPlusOne($difference);
    }

    private static function render(string $packed): string
    {
        if (self::byteLength($packed) === 4) {
            return \implode('.', \array_map(\ord(...), \str_split($packed)));
        }

        if (\str_starts_with($packed, \str_repeat("\x00", 10) . "\xFF\xFF")) {
            return '::ffff:' . self::render(\substr($packed, 12));
        }

        $groups = [];
        for ($i = 0; $i < 16; $i += 2) {
            $groups[] = (\ord($packed[$i]) << 8) | \ord($packed[$i + 1]);
        }

        [$runStart, $runLength] = self::longestZeroRun($groups);
        $hex = \array_map(\dechex(...), $groups);
        if ($runLength < 2) {
            return \implode(':', $hex);
        }

        return \implode(':', \array_slice($hex, 0, $runStart))
            . '::'
            . \implode(':', \array_slice($hex, $runStart + $runLength));
    }

    /**
     * Start and length of the leftmost longest run of zero groups.
     *
     * @param list<int> $groups
     *
     * @return array{int<0, 8>, int<0, 8>}
     */
    private static function longestZeroRun(array $groups): array
    {
        $bestStart = 0;
        $bestLength = 0;
        $start = 0;
        $length = 0;
        foreach ($groups as $index => $group) {
            if ($group !== 0) {
                $length = 0;

                continue;
            }

            if ($length === 0) {
                $start = $index;
            }

            $length++;
            if ($length > $bestLength) {
                $bestStart = $start;
                $bestLength = $length;
            }
        }

        return [\min(8, $bestStart), \min(8, $bestLength)];
    }

    /**
     * @return numeric-string
     */
    private static function toDecimalPlusOne(string $packed): string
    {
        $bytes = \array_map(\ord(...), \str_split($packed));

        $carry = 1;
        for ($i = \count($bytes) - 1; $i >= 0 && $carry > 0; $i--) {
            $sum = $bytes[$i] + $carry;
            $bytes[$i] = $sum & 0xFF;
            $carry = $sum >> 8;
        }

        if ($carry > 0) {
            \array_unshift($bytes, $carry);
        }

        $digits = '';
        while ($bytes !== []) {
            $remainder = 0;
            $quotient = [];
            foreach ($bytes as $byte) {
                $current = $remainder * 256 + $byte;
                $digit = \intdiv($current, 10);
                $remainder = $current % 10;
                if ($quotient !== [] || $digit !== 0) {
                    $quotient[] = $digit;
                }
            }

            $digits = $remainder . $digits;
            $bytes = $quotient;
        }

        if (!\is_numeric($digits)) {
            throw new LogicException('Cannot render a packed address as a decimal count.');
        }

        return $digits;
    }
}
