<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Finance;

use MSSTC4PHP\ValueObject\Exception\InvalidIbanException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Support\IbanFormat;
use MSSTC4PHP\ValueObject\Support\IbanRegistry;
use Override;

/**
 * International Bank Account Number (ISO 13616) with ISO 7064 mod-97 validation.
 * Stored in the compact, uppercase form (no spaces).
 *
 * The length and the BBAN character pattern of a country in the SWIFT IBAN Registry (the ISO 13616 registration
 * authority) are enforced; a country absent from it keeps the generic check.
 *
 * @see https://www.swift.com/standards/data-standards/iban-international-bank-account-number
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

    /**
     * The bank identifier at the position the registry gives for the country; null for a country absent from it.
     *
     * @return non-empty-string|null
     */
    public function bankIdentifier(): ?string
    {
        return $this->format()?->bankIdentifier($this->value);
    }

    /**
     * Null when the country is absent from the registry or the registry gives it no branch identifier.
     *
     * @return non-empty-string|null
     */
    public function branchIdentifier(): ?string
    {
        return $this->format()?->branchIdentifier($this->value);
    }

    /**
     * The registry's SEPA flag; null for a country absent from it.
     */
    public function isSepaCountry(): ?bool
    {
        return $this->format()?->sepa;
    }

    #[Override]
    protected function parse(string $value): string
    {
        $compact = \strtoupper(\str_replace(' ', '', $value));

        $length = \strlen($compact);
        if ($length < 15 || $length > 34 || \preg_match(self::PATTERN, $compact) !== 1) {
            throw new InvalidIbanException('Invalid IBAN.');
        }

        $format = IbanRegistry::find(\substr($compact, 0, 2));
        if ($format instanceof IbanFormat && !$format->accepts($compact)) {
            throw new InvalidIbanException('IBAN does not match the registered format of its country.');
        }

        if ($this->mod97($compact) !== 1) {
            throw new InvalidIbanException('IBAN fails the ISO 7064 checksum.');
        }

        return $compact;
    }

    private function format(): ?IbanFormat
    {
        return IbanRegistry::find($this->countryCode());
    }

    /**
     * ISO 7064 mod-97: move the first four characters to the end, map letters to
     * numbers (A=10 .. Z=35) and take the remainder modulo 97, digit by digit.
     */
    private function mod97(string $compact): int
    {
        $remainder = 0;
        foreach (\str_split(\substr($compact, 4) . \substr($compact, 0, 4)) as $char) {
            $numeric = \ctype_alpha($char) ? (string) (\ord($char) - 55) : $char;
            foreach (\str_split($numeric) as $digit) {
                $remainder = ($remainder * 10 + (int) $digit) % 97;
            }
        }

        return $remainder;
    }
}
