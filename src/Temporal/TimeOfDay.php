<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Temporal;

use MSSTC4PHP\ValueObject\Contract\Comparable;
use MSSTC4PHP\ValueObject\Exception\InvalidTimeOfDayException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Support\ComparableHelpers;

/**
 * A wall-clock time of day, stored in canonical HH:MM:SS form (seconds default to 00).
 */
final class TimeOfDay extends AbstractStringValueObject implements Comparable
{
    use ComparableHelpers;

    protected function parse(string $value): string
    {
        if (\preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value) !== 1) {
            throw new InvalidTimeOfDayException(\sprintf('Invalid time of day: "%s".', $value));
        }

        return \strlen($value) === 5 ? $value . ':00' : $value;
    }

    /**
     * Chronological ordering. The canonical HH:MM:SS storage makes a plain
     * string comparison equivalent to comparing the instant of day.
     */
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new InvalidTimeOfDayException(\sprintf('Cannot compare %s with %s.', self::class, $other::class));
        }

        return \strcmp($this->value, $other->value) <=> 0;
    }

    /**
     * Add a time-only duration, wrapping around midnight. A duration carrying
     * date components (years/months/days) is rejected — they have no meaning
     * for a wall-clock time.
     *
     * @throws InvalidTimeOfDayException when the duration has date components
     */
    public function plus(Duration $duration): self
    {
        return $this->shiftBy($this->durationSeconds($duration));
    }

    /**
     * @throws InvalidTimeOfDayException when the duration has date components
     */
    public function minus(Duration $duration): self
    {
        return $this->shiftBy(-$this->durationSeconds($duration));
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

    private function shiftBy(int $deltaSeconds): self
    {
        $current = $this->hour() * 3600 + $this->minute() * 60 + $this->second();
        $wrapped = (($current + $deltaSeconds) % 86400 + 86400) % 86400;

        return new self(\sprintf(
            '%02d:%02d:%02d',
            \intdiv($wrapped, 3600),
            \intdiv($wrapped % 3600, 60),
            $wrapped % 60,
        ));
    }

    /**
     * @throws InvalidTimeOfDayException when the duration carries date components
     */
    private function durationSeconds(Duration $duration): int
    {
        $interval = $duration->toDateInterval();
        if ($interval->y !== 0 || $interval->m !== 0 || $interval->d !== 0) {
            throw new InvalidTimeOfDayException(\sprintf('Duration "%s" has date components and cannot shift a time of day.', $duration));
        }

        return $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }
}
