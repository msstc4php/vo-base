<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidMacAddressException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Support\HexOctets;
use Override;

/**
 * A 48-bit MAC address. Accepts colon, hyphen, and dotted notations and stores
 * the canonical lowercase colon form (aa:bb:cc:dd:ee:ff), so equal addresses
 * written differently compare equal.
 */
final class MacAddress extends AbstractStringValueObject
{
    public function oui(): Oui
    {
        return new Oui(\substr($this->value, 0, 8));
    }

    #[Override]
    protected function parse(string $value): string
    {
        $canonical = HexOctets::canonical($value, 6, 'dotted');
        if ($canonical === null) {
            throw new InvalidMacAddressException(\sprintf('Value "%s" is not a valid MAC address.', InvalidMacAddressException::excerpt($value)));
        }

        return $canonical;
    }
}
