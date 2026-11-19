<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidOuiException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * A 24-bit IEEE Organizationally Unique Identifier: the first three octets of a MAC address or EUI-64.
 * Accepts colon (aa:bb:cc), hyphen (aa-bb-cc) and bare (aabbcc) notation and stores the canonical
 * lowercase colon form.
 *
 * @see https://standards.ieee.org/products-programs/regauth/
 */
final class Oui extends AbstractStringValueObject
{
    private const string COLON = '/^[0-9A-Fa-f]{2}(:[0-9A-Fa-f]{2}){2}$/D';

    private const string HYPHEN = '/^[0-9A-Fa-f]{2}(-[0-9A-Fa-f]{2}){2}$/D';

    private const string BARE = '/^[0-9A-Fa-f]{6}$/D';

    #[Override]
    protected function parse(string $value): string
    {
        $matchesNotation = \preg_match(self::COLON, $value) === 1
            || \preg_match(self::HYPHEN, $value) === 1
            || \preg_match(self::BARE, $value) === 1;

        if (!$matchesNotation) {
            throw new InvalidOuiException(\sprintf('Value "%s" is not a valid OUI.', InvalidOuiException::excerpt($value)));
        }

        $hex = \strtolower(\str_replace([':', '-'], '', $value));

        return \implode(':', \str_split($hex, 2));
    }
}
