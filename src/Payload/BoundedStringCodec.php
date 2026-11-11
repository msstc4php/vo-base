<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Payload;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidPayloadException;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use Override;

/**
 * {value: string, minLength: int, maxLength: int|null}. The bounds are part of
 * the value's identity (BoundedString::equals compares them), so this must NOT
 * delegate to jsonSerialize(), which returns the bare string.
 *
 * @implements PayloadCodec<BoundedString>
 */
final readonly class BoundedStringCodec implements PayloadCodec
{
    #[Override]
    public function valueObjectClass(): string
    {
        return BoundedString::class;
    }

    /**
     * @return array{value: string, minLength: int, maxLength: int|null}
     */
    #[Override]
    public function encode(ValueObject $value): array
    {
        if (!$value instanceof BoundedString) {
            throw new LogicException(\sprintf('%s encodes %s, %s given.', self::class, BoundedString::class, $value::class));
        }

        return [
            'value' => $value->value,
            'minLength' => $value->minLength,
            'maxLength' => $value->maxLength,
        ];
    }

    #[Override]
    public function decode(array $payload): BoundedString
    {
        $text = $payload['value'] ?? null;
        $minLength = $payload['minLength'] ?? null;
        $maxLength = $payload['maxLength'] ?? null;
        if (!\is_string($text) || !\is_int($minLength) || ($maxLength !== null && !\is_int($maxLength))) {
            throw new InvalidPayloadException('Expected a bounded-string payload: string "value", integer "minLength", integer or null "maxLength".');
        }

        return new BoundedString($text, $minLength, $maxLength);
    }
}
