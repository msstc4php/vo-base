<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Payload\PayloadCodec;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use Override;

/**
 * @implements PayloadCodec<IntRange>
 */
final readonly class RangeAsTextCodec implements PayloadCodec
{
    #[Override]
    public function valueObjectClass(): string
    {
        return IntRange::class;
    }

    /**
     * @return array{range: string}
     */
    #[Override]
    public function encode(ValueObject $value): array
    {
        if (!$value instanceof IntRange) {
            throw new LogicException('An IntRange is expected.');
        }

        return ['range' => $value->min . '..' . $value->max];
    }

    #[Override]
    public function decode(array $payload): IntRange
    {
        $bounds = explode('..', \is_string($payload['range'] ?? null) ? $payload['range'] : '');

        return new IntRange((int) $bounds[0], (int) ($bounds[1] ?? 0));
    }
}
