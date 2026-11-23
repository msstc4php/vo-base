<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Temporal;

use DateTimeImmutable;
use DateTimeInterface;
use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidDateRangeException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\FreshInstance;
use Override;

/**
 * A closed interval between two instants, inclusive of both endpoints.
 */
final readonly class DateRange implements ValueObject
{
    public function __construct(
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
    ) {
        $this->check($start, $end);
    }

    /**
     * @return array{start: DateTimeImmutable, end: DateTimeImmutable}
     */
    public function __serialize(): array
    {
        return ['start' => $this->start, 'end' => $this->end];
    }

    /**
     * Runs the constructor's checks again, so a forged or stale payload never yields an instance the constructor
     * would refuse. Other keys are ignored.
     *
     * @param array<array-key, mixed> $data untrusted: whatever the serialized string holds
     *
     * @throws InvalidValueObjectException when a key is missing or of the wrong type, or the values break the type's rules
     * @throws LogicException when called on an object that already holds a value
     */
    public function __unserialize(array $data): void
    {
        FreshInstance::assert($this, 'start');

        $start = $data['start'] ?? null;
        $end = $data['end'] ?? null;
        if (!$start instanceof DateTimeImmutable || !$end instanceof DateTimeImmutable) {
            throw new InvalidValueObjectException(\sprintf('A serialized %s must hold start and end as DateTimeImmutable.', self::class));
        }

        $this->check($start, $end);
        $this->start = $start;
        $this->end = $end;
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

    #[Override]
    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && ($this->start <=> $other->start) === 0
            && ($this->end <=> $other->end) === 0;
    }

    /**
     * @return array{start: string, end: string}
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'start' => $this->start->format(DateTimeInterface::ATOM),
            'end' => $this->end->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * @throws InvalidDateRangeException
     */
    private function check(DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        if ($start > $end) {
            throw new InvalidDateRangeException(\sprintf('Range start "%s" must not be after end "%s".', $start->format(DateTimeInterface::ATOM), $end->format(DateTimeInterface::ATOM)));
        }
    }
}
