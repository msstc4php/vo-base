<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Exception;

use InvalidArgumentException;

/**
 * Base type for every value-object construction failure. Each value object
 * throws its own subclass so callers can catch a specific kind or the whole family.
 */
class InvalidValueObjectException extends InvalidArgumentException
{
    private const int EXCERPT_BYTES = 200;

    private const string UTF8_OR_STRAY_BYTE = '/[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'
        . '|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}'
        . '|([\x80-\xFF])/';

    /**
     * The rejected input as it may appear in a message: backslashes and control
     * characters escaped (no CRLF in logs or headers, and a literal "\n" stays
     * distinguishable from a newline), bytes of invalid UTF-8 shown as \xNN, and
     * long input cut at a character boundary, so a JSON logger gets valid UTF-8.
     */
    public static function excerpt(string $value): string
    {
        if (\strlen($value) > self::EXCERPT_BYTES) {
            // A UTF-8 character spans at most four bytes, so at most three steps back reach its start.
            $cut = self::EXCERPT_BYTES;
            for ($step = 0; $step < 3 && (\ord($value[$cut]) & 0xC0) === 0x80; $step++) {
                $cut--;
            }

            $value = \substr($value, 0, $cut) . '...';
        }

        $escaped = \addcslashes($value, "\\\0..\37\177");

        // Valid multi-byte sequences stay readable; only stray bytes become \xNN.
        return \preg_replace_callback(
            self::UTF8_OR_STRAY_BYTE,
            static fn (array $match): string => isset($match[1]) ? \sprintf('\\x%02X', \ord($match[1])) : $match[0],
            $escaped,
        ) ?? \bin2hex($value);
    }
}
