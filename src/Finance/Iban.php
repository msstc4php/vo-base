<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Finance;

use MSSTC4PHP\ValueObject\Exception\InvalidIbanException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * International Bank Account Number (ISO 13616) with ISO 7064 mod-97 validation.
 * Stored in the compact, uppercase form (no spaces).
 */
final class Iban extends AbstractStringValueObject
{
    private const string PATTERN = '/^[A-Z]{2}\d{2}[A-Z0-9]{1,30}$/';

    protected function parse(string $value): string
    {
        $compact = \strtoupper(\str_replace(' ', '', $value));

        $length = \strlen($compact);
        if ($length < 15 || $length > 34 || \preg_match(self::PATTERN, $compact) !== 1) {
            throw new InvalidIbanException(\sprintf('Invalid IBAN: "%s".', $value));
        }

        if ($this->mod97($compact) !== '1') {
            throw new InvalidIbanException(\sprintf('IBAN "%s" fails the ISO 7064 checksum.', $value));
        }

        return $compact;
    }

    public function countryCode(): string
    {
        return \substr($this->value, 0, 2);
    }

    public function checkDigits(): string
    {
        return \substr($this->value, 2, 2);
    }

    /**
     * ISO 7064 mod-97: move the first four characters to the end, map letters to
     * numbers (A=10 .. Z=35) and take the remainder modulo 97.
     *
     * @return numeric-string
     */
    private function mod97(string $compact): string
    {
        $rearranged = \substr($compact, 4) . \substr($compact, 0, 4);

        $numeric = '';
        foreach (\str_split($rearranged) as $char) {
            $numeric .= \ctype_alpha($char) ? (string) (\ord($char) - 55) : $char;
        }

        // The rearranged string only contains digits after the letter mapping.
        \assert(\is_numeric($numeric));

        return \bcmod($numeric, '97');
    }
}
