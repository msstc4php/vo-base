<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Finance;

use MSSTC4PHP\ValueObject\Exception\InvalidBicException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * Business Identifier Code / SWIFT code (ISO 9362): 8 or 11 characters,
 * stored uppercase.
 */
final class Bic extends AbstractStringValueObject
{
    private const string PATTERN = '/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/';

    protected function parse(string $value): string
    {
        $upper = \strtoupper($value);

        if (\preg_match(self::PATTERN, $upper) !== 1) {
            throw new InvalidBicException(\sprintf('Invalid BIC: "%s".', $value));
        }

        return $upper;
    }

    public function bankCode(): string
    {
        return \substr($this->value, 0, 4);
    }

    public function countryCode(): string
    {
        return \substr($this->value, 4, 2);
    }

    public function locationCode(): string
    {
        return \substr($this->value, 6, 2);
    }

    public function branchCode(): ?string
    {
        return \strlen($this->value) === 11 ? \substr($this->value, 8, 3) : null;
    }
}
