<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidEui64Exception;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Support\HexOctets;
use Override;

/**
 * A 64-bit IEEE EUI-64 identifier. Accepts colon, hyphen and dotted notations and stores the canonical
 * lowercase colon form (aa:bb:cc:dd:ee:ff:00:11), so equal identifiers written differently compare equal.
 *
 * @see https://standards.ieee.org/wp-content/uploads/import/documents/tutorials/eui.pdf
 */
final class Eui64 extends AbstractStringValueObject
{
    public function oui(): Oui
    {
        return new Oui(\substr($this->value, 0, 8));
    }

    #[Override]
    protected function parse(string $value): string
    {
        $canonical = HexOctets::canonical($value, 8, 'dotted');
        if ($canonical === null) {
            throw new InvalidEui64Exception(\sprintf('Value "%s" is not a valid EUI-64.', InvalidEui64Exception::excerpt($value)));
        }

        return $canonical;
    }
}
