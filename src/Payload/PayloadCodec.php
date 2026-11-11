<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Payload;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidPayloadException;

/**
 * Turns a composite value object into a flat payload (a JSON column, a
 * sub-document) and back. Every framework bridge stores a composite through the
 * same codec, so a value written by one bridge reads back through another.
 *
 * @template-covariant TValue of ValueObject the composite value object this codec maps
 */
interface PayloadCodec
{
    /**
     * @return class-string<TValue>
     */
    public function valueObjectClass(): string;

    /**
     * Accepts any value object so that a codec for a narrower type is still a
     * PayloadCodec<ValueObject>; an instance of another class is a programmer error.
     *
     * @return array<string, scalar|null>
     *
     * @throws LogicException when $value is not an instance of {@see valueObjectClass()}
     */
    public function encode(ValueObject $value): array;

    /**
     * The payload comes from storage or the wire and has no trusted shape; decode()
     * narrows it.
     *
     * @param array<array-key, mixed> $payload
     *
     * @return TValue
     *
     * @throws InvalidPayloadException when the payload does not have the expected shape
     */
    public function decode(array $payload): ValueObject;
}
