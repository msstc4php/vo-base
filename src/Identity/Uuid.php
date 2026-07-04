<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Identity;

use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidUuidException;

/**
 * A universally unique identifier in canonical 8-4-4-4-12 hexadecimal form.
 *
 * Backed directly (not via the string base) so version/variant decoding and
 * generation helpers live on the type itself. The stored value is always
 * lowercased; generation never reads the wall clock (timestamps are passed in).
 */
final readonly class Uuid implements StringConvertible, ValueObject
{
    private const string CANONICAL_PATTERN = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';

    public string $value;

    public function __construct(string $value)
    {
        if (\preg_match(self::CANONICAL_PATTERN, $value) !== 1) {
            throw new InvalidUuidException(\sprintf('Value "%s" is not a canonical UUID.', $value));
        }

        $this->value = \strtolower($value);
    }

    public static function from(string $value): static
    {
        return new self($value);
    }

    public static function tryFrom(string $value): ?self
    {
        try {
            return new self($value);
        } catch (InvalidUuidException) {
            return null;
        }
    }

    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) instanceof self;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->value === $this->value;
    }

    /**
     * The version nibble (first hex digit of the 3rd group); 0 for the nil UUID.
     *
     * @return int<0, 15>
     */
    public function version(): int
    {
        return (int) \hexdec($this->value[14]) & 0xF;
    }

    /**
     * The RFC 4122 variant decoded from the first hex digit of the 4th group.
     *
     * @return int<0, 3>
     */
    public function variant(): int
    {
        $nibble = (int) \hexdec($this->value[19]) & 0xF;

        return match (true) {
            ($nibble & 0b1000) === 0 => 0,
            ($nibble & 0b0100) === 0 => 1,
            ($nibble & 0b0010) === 0 => 2,
            default => 3,
        };
    }

    /**
     * Generate a random version-4 UUID with the RFC 4122 variant bits.
     */
    public static function v4(): self
    {
        $bytes = \random_bytes(16);
        $bytes[6] = \chr((\ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = \chr((\ord($bytes[8]) & 0x3F) | 0x80);

        return new self(self::format($bytes));
    }

    /**
     * Generate a version-7 UUID whose first 48 bits are the big-endian
     * millisecond timestamp; the remaining bytes are random.
     *
     * @param non-negative-int $unixMillis
     */
    public static function v7(int $unixMillis): self
    {
        $timeHex = \substr(\str_pad(\dechex($unixMillis), 12, '0', \STR_PAD_LEFT), -12);
        $timeBytes = (string) \hex2bin($timeHex);

        $bytes = $timeBytes . \random_bytes(10);
        $bytes[6] = \chr((\ord($bytes[6]) & 0x0F) | 0x70);
        $bytes[8] = \chr((\ord($bytes[8]) & 0x3F) | 0x80);

        return new self(self::format($bytes));
    }

    /**
     * Convenience wrapper over {@see v7()} that reads the current wall-clock time.
     * Prefer passing an explicit timestamp to v7() where determinism matters.
     */
    public static function v7Now(): self
    {
        return self::v7(\max(0, (int) (\microtime(true) * 1000)));
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * Render 16 raw bytes as the canonical 8-4-4-4-12 hexadecimal string.
     *
     * @param non-empty-string $bytes
     *
     * @return non-empty-string
     */
    private static function format(string $bytes): string
    {
        $hex = \bin2hex($bytes);

        return \sprintf(
            '%s-%s-%s-%s-%s',
            \substr($hex, 0, 8),
            \substr($hex, 8, 4),
            \substr($hex, 12, 4),
            \substr($hex, 16, 4),
            \substr($hex, 20, 12),
        );
    }
}
