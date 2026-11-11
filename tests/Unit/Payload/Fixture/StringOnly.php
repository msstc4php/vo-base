<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture;

use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use Override;

/** StringConvertible without being a ValueObject: not something the mapper may store. */
final readonly class StringOnly implements StringConvertible
{
    private function __construct(
        private string $value,
    ) {
    }

    #[Override]
    public function __toString(): string
    {
        return $this->value;
    }

    #[Override]
    public static function from(string $value): static
    {
        return new self($value);
    }
}
