<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Contract;

use Stringable;

/**
 * A value object with a lossless string representation: constructible from a
 * string via {@see from()} and rendered back via {@see __toString()}. This is
 * the contract a persistence bridge relies on to map any string-backed
 * value object generically.
 */
interface StringConvertible extends Stringable
{
    public static function from(string $value): static;
}
