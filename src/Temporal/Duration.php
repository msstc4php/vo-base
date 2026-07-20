<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Temporal;

use DateInterval;
use DateTimeImmutable;
use Exception;
use MSSTC4PHP\ValueObject\Exception\InvalidDurationException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * An ISO 8601 duration (e.g. "P1Y2M10DT2H30M"), stored uppercased.
 *
 * Arithmetic works on three calendar-safe buckets — months (years fold in at
 * 12), days, and seconds (hours/minutes fold in at 60) — because months and
 * days have no fixed second count. Comparison across durations is deliberately
 * not offered; use {@see toSeconds()} with an anchor for that.
 */
final class Duration extends AbstractStringValueObject
{
    public static function fromSeconds(int $seconds): self
    {
        if ($seconds < 0) {
            throw new InvalidDurationException(\sprintf('A duration cannot be negative (%d seconds).', $seconds));
        }

        return self::fromBuckets(0, 0, $seconds);
    }

    protected function parse(string $value): string
    {
        $normalized = \strtoupper($value);

        if (\preg_match('/^P(?!$)(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(?!$)(\d+H)?(\d+M)?(\d+S)?)?$/', $normalized) !== 1) {
            throw new InvalidDurationException(\sprintf('Invalid ISO 8601 duration: "%s".', $value));
        }

        try {
            new DateInterval($normalized);
        } catch (Exception $exception) {
            throw new InvalidDurationException(\sprintf('Invalid ISO 8601 duration: "%s".', $value), 0, $exception);
        }

        return $normalized;
    }

    public function toDateInterval(): DateInterval
    {
        return new DateInterval($this->value);
    }

    public function isZero(): bool
    {
        return $this->totalMonths() === 0 && $this->toDateInterval()->d === 0 && $this->totalSeconds() === 0;
    }

    public function plus(self $other): self
    {
        return self::fromBuckets(
            $this->totalMonths() + $other->totalMonths(),
            $this->toDateInterval()->d + $other->days(),
            $this->totalSeconds() + $other->totalSeconds(),
        );
    }

    /**
     * @throws InvalidDurationException when the result would be negative in any bucket
     */
    public function minus(self $other): self
    {
        $months = $this->totalMonths() - $other->totalMonths();
        $days = $this->toDateInterval()->d - $other->days();
        $seconds = $this->totalSeconds() - $other->totalSeconds();

        if ($months < 0 || $days < 0 || $seconds < 0) {
            throw new InvalidDurationException(\sprintf('Subtracting "%s" from "%s" yields a negative duration.', $other->value, $this->value));
        }

        return self::fromBuckets($months, $days, $seconds);
    }

    /**
     * @throws InvalidDurationException when the factor is negative
     */
    public function multipliedBy(int $factor): self
    {
        if ($factor < 0) {
            throw new InvalidDurationException(\sprintf('Cannot multiply a duration by a negative factor (%d).', $factor));
        }

        return self::fromBuckets(
            $this->totalMonths() * $factor,
            $this->toDateInterval()->d * $factor,
            $this->totalSeconds() * $factor,
        );
    }

    /**
     * The duration in seconds. Year/month components are calendar-dependent, so
     * an anchor date is required whenever they are present; days without an
     * anchor are treated as fixed 86400-second days.
     *
     * @throws InvalidDurationException when year/month components are present but no anchor is given
     */
    public function toSeconds(?DateTimeImmutable $anchor = null): int
    {
        if ($anchor instanceof DateTimeImmutable) {
            return $anchor->add($this->toDateInterval())->getTimestamp() - $anchor->getTimestamp();
        }

        if ($this->totalMonths() !== 0) {
            throw new InvalidDurationException(\sprintf('Duration "%s" has calendar components; toSeconds() requires an anchor date.', $this->value));
        }

        return $this->toDateInterval()->d * 86400 + $this->totalSeconds();
    }

    private function totalMonths(): int
    {
        $interval = $this->toDateInterval();

        return $interval->y * 12 + $interval->m;
    }

    private function days(): int
    {
        return $this->toDateInterval()->d;
    }

    private function totalSeconds(): int
    {
        $interval = $this->toDateInterval();

        return $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }

    private static function fromBuckets(int $months, int $days, int $seconds): self
    {
        $years = \intdiv($months, 12);
        $remainingMonths = $months % 12;
        $hours = \intdiv($seconds, 3600);
        $minutes = \intdiv($seconds % 3600, 60);
        $secondsPart = $seconds % 60;

        $date = '';
        if ($years !== 0) {
            $date .= $years . 'Y';
        }
        if ($remainingMonths !== 0) {
            $date .= $remainingMonths . 'M';
        }
        if ($days !== 0) {
            $date .= $days . 'D';
        }

        $time = '';
        if ($hours !== 0) {
            $time .= $hours . 'H';
        }
        if ($minutes !== 0) {
            $time .= $minutes . 'M';
        }
        if ($secondsPart !== 0) {
            $time .= $secondsPart . 'S';
        }

        $iso = 'P' . $date . ($time === '' ? '' : 'T' . $time);

        return new self($iso === 'P' ? 'PT0S' : $iso);
    }
}
