<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidOuiException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Support\HexOctets;
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
    #[Override]
    protected function parse(string $value): string
    {
        $canonical = HexOctets::canonical($value, 3, 'bare');
        if ($canonical === null) {
            throw new InvalidOuiException(\sprintf('Value "%s" is not a valid OUI.', InvalidOuiException::excerpt($value)));
        }

        return $canonical;
    }
}
