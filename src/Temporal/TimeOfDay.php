<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Temporal;

use MSSTC4PHP\ValueObject\Exception\InvalidTimeOfDayException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * A wall-clock time of day, stored in canonical HH:MM:SS form (seconds default to 00).
 */
final class TimeOfDay extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        if (\preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value) !== 1) {
            throw new InvalidTimeOfDayException(\sprintf('Invalid time of day: "%s".', $value));
        }

        return \strlen($value) === 5 ? $value . ':00' : $value;
    }

    public function hour(): int
    {
        return (int) \substr($this->value, 0, 2);
    }

    public function minute(): int
    {
        return (int) \substr($this->value, 3, 2);
    }

    public function second(): int
    {
        return (int) \substr($this->value, 6, 2);
    }
}
