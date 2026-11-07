<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Finance;

use MSSTC4PHP\ValueObject\Exception\InvalidIbanException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * International Bank Account Number (ISO 13616) with ISO 7064 mod-97 validation.
 * Stored in the compact, uppercase form (no spaces).
 */
final class Iban extends AbstractStringValueObject
{
    private const string PATTERN = '/^[A-Z]{2}\d{2}[A-Z0-9]{1,30}$/D';

    public function countryCode(): string
    {
        return \substr($this->value, 0, 2);
    }

    public function checkDigits(): string
    {
        return \substr($this->value, 2, 2);
    }

    #[Override]
    protected function parse(string $value): string
    {
        $compact = \strtoupper(\str_replace(' ', '', $value));

        $length = \strlen($compact);
        if ($length < 15 || $length > 34 || \preg_match(self::PATTERN, $compact) !== 1) {
            throw new InvalidIbanException(\sprintf('Invalid IBAN (%s).', $this->masked($value)));
        }

        if ($this->mod97($compact) !== '1') {
            throw new InvalidIbanException(\sprintf('IBAN %s fails the ISO 7064 checksum.', $this->masked($value)));
        }

        return $compact;
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

    /**
     * An account number is personal data: messages keep only the country and the last four characters, and
     * nothing of input that is not IBAN-shaped (it may be any value typed into the wrong field, a card number
     * included).
     */
    private function masked(string $value): string
    {
        $compact = \strtoupper(\str_replace(' ', '', $value));
        if (\strlen($compact) < 15 || \strlen($compact) > 34 || \preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]+$/D', $compact) !== 1) {
            return \sprintf('%d characters', \strlen($value));
        }

        return \substr($compact, 0, 2) . \str_repeat('*', \strlen($compact) - 6) . \substr($compact, -4);
    }
}
