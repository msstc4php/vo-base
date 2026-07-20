<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidIntRangeException;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RangeException;

#[CoversClass(IntRange::class)]
final class IntRangeTest extends TestCase
{
    public function testMinGreaterThanMaxThrows(): void
    {
        $this->expectException(InvalidIntRangeException::class);

        new IntRange(5, 1);
    }

    public function testContainsIsInclusive(): void
    {
        $range = new IntRange(1, 10);

        self::assertTrue($range->contains(5));
        self::assertTrue($range->contains(1));
        self::assertTrue($range->contains(10));
        self::assertFalse($range->contains(0));
        self::assertFalse($range->contains(11));
    }

    public function testEqualsComparesBounds(): void
    {
        self::assertTrue(new IntRange(1, 10)->equals(new IntRange(1, 10)));
        self::assertFalse(new IntRange(1, 10)->equals(new IntRange(1, 9)));
        self::assertFalse(new IntRange(1, 10)->equals(new IntRange(0, 10)));
    }

    public function testLength(): void
    {
        self::assertSame(5, new IntRange(1, 5)->length());
        self::assertSame(1, new IntRange(3, 3)->length());
    }

    public function testLengthOverflowThrows(): void
    {
        $this->expectException(RangeException::class);

        new IntRange(\PHP_INT_MIN, \PHP_INT_MAX)->length();
    }

    public function testLengthAtIntegerCeilingThrows(): void
    {
        $this->expectException(RangeException::class);

        // max - min == PHP_INT_MAX, so + 1 would overflow.
        new IntRange(0, \PHP_INT_MAX)->length();
    }

    public function testOverlapsAndIntersect(): void
    {
        $intersection = new IntRange(1, 5)->intersect(new IntRange(4, 9));

        self::assertTrue(new IntRange(1, 5)->overlaps(new IntRange(4, 9)));
        self::assertNotNull($intersection);
        self::assertSame(4, $intersection->min);
        self::assertSame(5, $intersection->max);
    }

    public function testDisjointRangesDoNotIntersect(): void
    {
        self::assertFalse(new IntRange(1, 2)->overlaps(new IntRange(5, 6)));
        self::assertNull(new IntRange(1, 3)->intersect(new IntRange(4, 6)));
    }

    public function testOverlapsAtSharedBoundaryPoint(): void
    {
        self::assertTrue(new IntRange(1, 5)->overlaps(new IntRange(5, 9)));

        $intersection = new IntRange(1, 5)->intersect(new IntRange(5, 9));
        self::assertNotNull($intersection);
        self::assertSame(5, $intersection->min);
        self::assertSame(5, $intersection->max);
    }
}
