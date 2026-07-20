<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Temporal;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Exception\InvalidDateRangeException;
use MSSTC4PHP\ValueObject\Temporal\DateRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateRange::class)]
final class DateRangeTest extends TestCase
{
    public function testConstructsValidRange(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-01-31T00:00:00+00:00'),
        );

        self::assertEquals(new DateTimeImmutable('2026-01-01T00:00:00+00:00'), $range->start());
        self::assertEquals(new DateTimeImmutable('2026-01-31T00:00:00+00:00'), $range->end());
    }

    public function testRejectsStartAfterEnd(): void
    {
        $this->expectException(InvalidDateRangeException::class);

        new DateRange(
            new DateTimeImmutable('2026-02-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
    }

    public function testContainsIsInclusive(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-01-31T00:00:00+00:00'),
        );

        self::assertTrue($range->contains(new DateTimeImmutable('2026-01-15T00:00:00+00:00')));
        self::assertTrue($range->contains(new DateTimeImmutable('2026-01-01T00:00:00+00:00')));
        self::assertTrue($range->contains(new DateTimeImmutable('2026-01-31T00:00:00+00:00')));
        self::assertFalse($range->contains(new DateTimeImmutable('2025-12-31T23:59:59+00:00')));
        self::assertFalse($range->contains(new DateTimeImmutable('2026-02-01T00:00:00+00:00')));
    }

    public function testOverlapsDetectsOverlappingRanges(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-01-31T00:00:00+00:00'),
        );

        $overlapping = new DateRange(
            new DateTimeImmutable('2026-01-20T00:00:00+00:00'),
            new DateTimeImmutable('2026-02-10T00:00:00+00:00'),
        );

        $disjoint = new DateRange(
            new DateTimeImmutable('2026-03-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-03-31T00:00:00+00:00'),
        );

        self::assertTrue($range->overlaps($overlapping));
        self::assertFalse($range->overlaps($disjoint));
    }

    public function testEquals(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-01-31T00:00:00+00:00'),
        );

        $same = new DateRange(
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-01-31T00:00:00+00:00'),
        );

        $different = new DateRange(
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-02-28T00:00:00+00:00'),
        );

        self::assertTrue($range->equals($same));
        self::assertFalse($range->equals($different));
    }

    public function testJsonSerializeShape(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-01-31T12:30:00+00:00'),
        );

        self::assertSame(
            [
                'start' => '2026-01-01T00:00:00+00:00',
                'end' => '2026-01-31T12:30:00+00:00',
            ],
            $range->jsonSerialize(),
        );
    }

    public function testDuration(): void
    {
        $range = new DateRange(
            new DateTimeImmutable('2026-01-01T10:00:00Z'),
            new DateTimeImmutable('2026-01-01T11:00:00Z'),
        );

        self::assertSame('PT1H', $range->duration()->value);
    }

    public function testIntersectOverlapping(): void
    {
        $a = new DateRange(new DateTimeImmutable('2026-01-01T00:00:00Z'), new DateTimeImmutable('2026-01-10T00:00:00Z'));
        $b = new DateRange(new DateTimeImmutable('2026-01-05T00:00:00Z'), new DateTimeImmutable('2026-01-20T00:00:00Z'));

        $intersection = $a->intersect($b);

        self::assertNotNull($intersection);
        self::assertEquals(new DateTimeImmutable('2026-01-05T00:00:00Z'), $intersection->start);
        self::assertEquals(new DateTimeImmutable('2026-01-10T00:00:00Z'), $intersection->end);
    }

    public function testDisjointRangesIntersectNullAndGapNonNull(): void
    {
        $a = new DateRange(new DateTimeImmutable('2026-01-01T00:00:00Z'), new DateTimeImmutable('2026-01-05T00:00:00Z'));
        $b = new DateRange(new DateTimeImmutable('2026-01-10T00:00:00Z'), new DateTimeImmutable('2026-01-15T00:00:00Z'));

        self::assertNull($a->intersect($b));

        $gap = $a->gap($b);
        self::assertNotNull($gap);
        self::assertEquals(new DateTimeImmutable('2026-01-05T00:00:00Z'), $gap->start);
        self::assertEquals(new DateTimeImmutable('2026-01-10T00:00:00Z'), $gap->end);
    }

    public function testTouchingRangesAreAdjacentWithNoGap(): void
    {
        $a = new DateRange(new DateTimeImmutable('2026-01-01T00:00:00Z'), new DateTimeImmutable('2026-01-05T00:00:00Z'));
        $b = new DateRange(new DateTimeImmutable('2026-01-05T00:00:00Z'), new DateTimeImmutable('2026-01-10T00:00:00Z'));

        self::assertTrue($a->isAdjacentTo($b));
        self::assertNull($a->gap($b));
    }

    public function testContainsRange(): void
    {
        $outer = new DateRange(new DateTimeImmutable('2026-01-01T00:00:00Z'), new DateTimeImmutable('2026-01-31T00:00:00Z'));
        $inner = new DateRange(new DateTimeImmutable('2026-01-10T00:00:00Z'), new DateTimeImmutable('2026-01-20T00:00:00Z'));

        self::assertTrue($outer->contains($inner));
        self::assertFalse($inner->contains($outer));
    }
}
