<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidBase64StringException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Strict base64-encoded string. The stored value round-trips through decode/encode.
 */
final class Base64String extends AbstractStringValueObject
{
    /**
     * Decode the stored value back to its raw bytes.
     */
    public function decoded(): string
    {
        // Validated in parse(): the value is strict base64, so decoding cannot fail.
        return (string) \base64_decode($this->value, true);
    }

    #[Override]
    protected function parse(string $value): string
    {
        $decoded = \base64_decode($value, true);

        if ($decoded === false || \base64_encode($decoded) !== $value) {
            throw new InvalidBase64StringException(\sprintf('Invalid base64 string: "%s".', InvalidBase64StringException::excerpt($value)));
        }

        return $value;
    }
}
