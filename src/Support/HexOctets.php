<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

use LogicException;

/**
 * Notation handling shared by the IEEE identifiers (MAC address, EUI-64, OUI).
 *
 * @internal
 */
final class HexOctets
{
    /**
     * The canonical lowercase colon form of an identifier of $octets octets written with colons, hyphens, or
     * the alternative form: Cisco-style dotted groups of four digits ('dotted') or no separator at all ('bare').
     * Null when the value is in none of those notations.
     *
     * @param positive-int $octets
     * @param 'dotted'|'bare' $alternative
     *
     * @throws LogicException when the dotted notation is asked for an odd number of octets
     */
    public static function canonical(string $value, int $octets, string $alternative): ?string
    {
        if ($alternative === 'dotted' && $octets % 2 !== 0) {
            throw new LogicException('The dotted notation needs an even number of octets.');
        }

        $patterns = [
            \sprintf('/^[0-9A-Fa-f]{2}(:[0-9A-Fa-f]{2}){%d}$/D', $octets - 1),
            \sprintf('/^[0-9A-Fa-f]{2}(-[0-9A-Fa-f]{2}){%d}$/D', $octets - 1),
            $alternative === 'dotted'
                ? \sprintf('/^[0-9A-Fa-f]{4}(\.[0-9A-Fa-f]{4}){%d}$/D', intdiv($octets, 2) - 1)
                : \sprintf('/^[0-9A-Fa-f]{%d}$/D', $octets * 2),
        ];

        foreach ($patterns as $pattern) {
            if (\preg_match($pattern, $value) === 1) {
                $hex = \strtolower(\str_replace([':', '-', '.'], '', $value));

                return \implode(':', \str_split($hex, 2));
            }
        }

        return null;
    }
}
