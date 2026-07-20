<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Temporal;

use DateTimeImmutable;
use DateTimeInterface;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidDateRangeException;

/**
 * A closed interval between two instants, inclusive of both endpoints.
 */
final readonly class DateRange implements ValueObject
{
    public function __construct(
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
    ) {
        if ($start > $end) {
            throw new InvalidDateRangeException(\sprintf('Range start "%s" must not be after end "%s".', $start->format(DateTimeInterface::ATOM), $end->format(DateTimeInterface::ATOM)));
        }
    }

    public function start(): DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * Whether the given instant or range falls entirely within this range.
     */
    public function contains(DateTimeImmutable|self $value): bool
    {
        if ($value instanceof self) {
            return $this->start <= $value->start && $value->end <= $this->end;
        }

        return $value >= $this->start && $value <= $this->end;
    }

    public function overlaps(self $other): bool
    {
        return $this->start <= $other->end && $other->start <= $this->end;
    }

    /**
     * The elapsed wall-clock time between the endpoints, as a second-granular
     * duration. The result fills only the seconds bucket, so a one-year range
     * renders as e.g. "PT8760H" rather than "P1Y"; sub-second precision is
     * dropped (Duration is second-granular).
     */
    public function duration(): Duration
    {
        return Duration::fromSeconds($this->end->getTimestamp() - $this->start->getTimestamp());
    }

    /**
     * The overlapping range shared with the other, or null when they are disjoint.
     */
    public function intersect(self $other): ?self
    {
        if (!$this->overlaps($other)) {
            return null;
        }

        return new self(
            $this->start >= $other->start ? $this->start : $other->start,
            $this->end <= $other->end ? $this->end : $other->end,
        );
    }

    /**
     * The range spanning the space between two disjoint ranges, or null when
     * they overlap or merely touch.
     */
    public function gap(self $other): ?self
    {
        if ($this->overlaps($other)) {
            return null;
        }

        [$first, $second] = $this->end < $other->start ? [$this, $other] : [$other, $this];

        return new self($first->end, $second->start);
    }

    /**
     * Whether the two ranges touch at exactly one endpoint without overlapping further.
     */
    public function isAdjacentTo(self $other): bool
    {
        return ($this->end <=> $other->start) === 0 || ($other->end <=> $this->start) === 0;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && ($this->start <=> $other->start) === 0
            && ($this->end <=> $other->end) === 0;
    }

    /**
     * @return array{start: string, end: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'start' => $this->start->format(DateTimeInterface::ATOM),
            'end' => $this->end->format(DateTimeInterface::ATOM),
        ];
    }
}
