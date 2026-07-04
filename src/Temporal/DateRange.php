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

    public function contains(DateTimeImmutable $point): bool
    {
        return $point >= $this->start && $point <= $this->end;
    }

    public function overlaps(self $other): bool
    {
        return $this->start <= $other->end && $other->start <= $this->end;
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
