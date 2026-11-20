<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

use InvalidArgumentException;
use LogicException;

/**
 * The registered IBAN format of one country: the facts of its SWIFT IBAN Registry entry that Iban checks and
 * reads. Positions are zero-based offsets into the BBAN (the part after the country code and check digits).
 *
 * @internal
 */
final readonly class IbanFormat
{
    /**
     * Positions are checked at runtime: the length lies in 15..34, starts are not negative, lengths are positive,
     * and $branchStart and $branchLength are given together or not at all (null when the registry gives the country
     * no branch identifier).
     *
     * @throws InvalidArgumentException when a value is out of range or only one of the branch values is given
     */
    public function __construct(
        public int $length,
        public string $bbanPattern,
        public int $bankStart,
        public int $bankLength,
        public bool $sepa,
        public ?int $branchStart = null,
        public ?int $branchLength = null,
    ) {
        if ($length < 15 || $length > 34 || $bbanPattern === '' || $bankStart < 0 || $bankLength < 1) {
            throw new InvalidArgumentException('IBAN format out of range.');
        }
        if (($branchStart === null) !== ($branchLength === null)) {
            throw new InvalidArgumentException('A branch identifier needs both its start and its length.');
        }
        if ($branchStart !== null && ($branchStart < 0 || $branchLength < 1)) {
            throw new InvalidArgumentException('IBAN format out of range.');
        }
    }

    public function accepts(string $compact): bool
    {
        return \strlen($compact) === $this->length && \preg_match($this->bbanPattern, \substr($compact, 4)) === 1;
    }

    /**
     * @return non-empty-string
     */
    public function bankIdentifier(string $compact): string
    {
        return $this->slice($compact, $this->bankStart, $this->bankLength);
    }

    /**
     * @return non-empty-string|null
     */
    public function branchIdentifier(string $compact): ?string
    {
        if ($this->branchStart === null || $this->branchLength === null) {
            return null;
        }

        return $this->slice($compact, $this->branchStart, $this->branchLength);
    }

    /**
     * @return non-empty-string
     *
     * @throws LogicException when the IBAN is too short for the registered position
     */
    private function slice(string $compact, int $start, int $length): string
    {
        $slice = \substr($compact, 4 + $start, $length);
        if ($slice === '') {
            throw new LogicException('The IBAN is shorter than its registered format.');
        }

        return $slice;
    }
}
