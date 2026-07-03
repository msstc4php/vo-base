<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidMacAddressException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * A 48-bit MAC address. Accepts colon, hyphen, and dotted notations and stores
 * the canonical lowercase colon form (aa:bb:cc:dd:ee:ff), so equal addresses
 * written differently compare equal.
 */
final class MacAddress extends AbstractStringValueObject
{
    private const string COLON = '/^[0-9A-Fa-f]{2}(:[0-9A-Fa-f]{2}){5}$/';

    private const string HYPHEN = '/^[0-9A-Fa-f]{2}(-[0-9A-Fa-f]{2}){5}$/';

    private const string DOTTED = '/^[0-9A-Fa-f]{4}(\.[0-9A-Fa-f]{4}){2}$/';

    protected function parse(string $value): string
    {
        $matchesNotation = \preg_match(self::COLON, $value) === 1
            || \preg_match(self::HYPHEN, $value) === 1
            || \preg_match(self::DOTTED, $value) === 1;

        if (!$matchesNotation) {
            throw new InvalidMacAddressException(\sprintf('Value "%s" is not a valid MAC address.', $value));
        }

        $hex = \strtolower(\str_replace([':', '-', '.'], '', $value));

        return \implode(':', \str_split($hex, 2));
    }
}
