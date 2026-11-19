<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidEui64Exception;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * A 64-bit IEEE EUI-64 identifier. Accepts colon, hyphen and dotted notations and stores the canonical
 * lowercase colon form (aa:bb:cc:dd:ee:ff:00:11), so equal identifiers written differently compare equal.
 *
 * @see https://standards.ieee.org/wp-content/uploads/import/documents/tutorials/eui.pdf
 */
final class Eui64 extends AbstractStringValueObject
{
    private const string COLON = '/^[0-9A-Fa-f]{2}(:[0-9A-Fa-f]{2}){7}$/D';

    private const string HYPHEN = '/^[0-9A-Fa-f]{2}(-[0-9A-Fa-f]{2}){7}$/D';

    private const string DOTTED = '/^[0-9A-Fa-f]{4}(\.[0-9A-Fa-f]{4}){3}$/D';

    public function oui(): Oui
    {
        return new Oui(\substr($this->value, 0, 8));
    }

    #[Override]
    protected function parse(string $value): string
    {
        $matchesNotation = \preg_match(self::COLON, $value) === 1
            || \preg_match(self::HYPHEN, $value) === 1
            || \preg_match(self::DOTTED, $value) === 1;

        if (!$matchesNotation) {
            throw new InvalidEui64Exception(\sprintf('Value "%s" is not a valid EUI-64.', InvalidEui64Exception::excerpt($value)));
        }

        $hex = \strtolower(\str_replace([':', '-', '.'], '', $value));

        return \implode(':', \str_split($hex, 2));
    }
}
