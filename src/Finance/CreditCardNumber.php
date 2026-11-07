<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Finance;

use MSSTC4PHP\ValueObject\Exception\InvalidCreditCardNumberException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Primary Account Number (PAN): 12..19 digits passing the Luhn checksum.
 * Separators (spaces, hyphens) are stripped before storage.
 */
final class CreditCardNumber extends AbstractStringValueObject
{
    public function last4(): string
    {
        return \substr($this->value, -4);
    }

    /**
     * @return 'visa'|'mastercard'|'amex'|'discover'|'unknown'
     */
    public function brand(): string
    {
        return match (true) {
            \preg_match('/^4/', $this->value) === 1 => 'visa',
            \preg_match('/^(5[1-5]|2[2-7])/', $this->value) === 1 => 'mastercard',
            \preg_match('/^3[47]/', $this->value) === 1 => 'amex',
            \preg_match('/^6(?:011|5)/', $this->value) === 1 => 'discover',
            default => 'unknown',
        };
    }

    #[Override]
    protected function parse(string $value): string
    {
        $digits = \str_replace([' ', '-'], '', $value);

        if (\preg_match('/^\d{12,19}$/D', $digits) !== 1 || !$this->hasValidLuhn($digits)) {
            // A number failing Luhn is often a real card number with a typo, and raw input may end in a CVV that still
            // looks like part of the number: no digit of it ever goes into a message (PCI DSS).
            throw new InvalidCreditCardNumberException(\sprintf('Invalid credit card number (%d characters).', \strlen($value)));
        }

        return $digits;
    }

    private function hasValidLuhn(string $digits): bool
    {
        $sum = 0;
        $double = false;
        for ($i = \strlen($digits) - 1; $i >= 0; $i--) {
            $digit = (int) $digits[$i];
            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $double = !$double;
        }

        return $sum % 10 === 0;
    }
}
