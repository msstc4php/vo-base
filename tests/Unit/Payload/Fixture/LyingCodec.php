<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Payload\PayloadCodec;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use Override;

/**
 * Claims IntRange but decodes into another class: the mapper must not hand that out as an IntRange.
 *
 * @implements PayloadCodec<ValueObject>
 */
final readonly class LyingCodec implements PayloadCodec
{
    public function __construct(
        private ValueObject $decoded,
    ) {
    }

    #[Override]
    public function valueObjectClass(): string
    {
        return IntRange::class;
    }

    #[Override]
    public function encode(ValueObject $value): array
    {
        return [];
    }

    #[Override]
    public function decode(array $payload): ValueObject
    {
        return $this->decoded;
    }
}
