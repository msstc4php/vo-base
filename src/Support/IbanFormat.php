<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

/**
 * The registered IBAN format of one country: the facts of its SWIFT IBAN Registry entry that Iban checks and
 * reads. Positions are zero-based offsets into the BBAN (the part after the country code and check digits).
 *
 * @internal
 */
final readonly class IbanFormat
{
    /**
     * @param int<15, 34> $length total IBAN length
     * @param non-empty-string $bbanPattern anchored pattern of the BBAN
     * @param non-negative-int $bankStart
     * @param positive-int $bankLength
     * @param non-negative-int|null $branchStart null when the registry gives the country no branch identifier
     * @param positive-int|null $branchLength
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
    }

    public function accepts(string $compact): bool
    {
        return \strlen($compact) === $this->length && \preg_match($this->bbanPattern, \substr($compact, 4)) === 1;
    }

    public function bankIdentifier(string $compact): string
    {
        return \substr($compact, 4 + $this->bankStart, $this->bankLength);
    }

    public function branchIdentifier(string $compact): ?string
    {
        if ($this->branchStart === null || $this->branchLength === null) {
            return null;
        }

        return \substr($compact, 4 + $this->branchStart, $this->branchLength);
    }
}
