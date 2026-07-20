<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidIntRangeException;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
        // Negative min with a valid (non-overflowing) span must not trip the guard.
        self::assertSame(11, new IntRange(-5, 5)->length());
        self::assertSame(6, new IntRange(-5, 0)->length());
    }

    public function testLengthAtMaxRepresentableDoesNotThrow(): void
    {
        // The largest span that still fits: max - min == PHP_INT_MAX - 1, so the
        // count is exactly PHP_INT_MAX. This must NOT trip either guard.
        self::assertSame(\PHP_INT_MAX, new IntRange(0, \PHP_INT_MAX - 1)->length());
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

    /**
     * Invariant: intersection is commutative (a∩b == b∩a).
     */
    #[DataProvider('rangePairProvider')]
    public function testIntersectIsCommutative(int $aMin, int $aMax, int $bMin, int $bMax): void
    {
        $a = new IntRange($aMin, $aMax);
        $b = new IntRange($bMin, $bMax);

        $ab = $a->intersect($b);
        $ba = $b->intersect($a);

        if (!$ab instanceof IntRange || !$ba instanceof IntRange) {
            self::assertNull($ab);
            self::assertNull($ba);

            return;
        }

        self::assertTrue($ab->equals($ba));
    }

    /**
     * @return iterable<string, array{int, int, int, int}>
     */
    public static function rangePairProvider(): iterable
    {
        yield 'overlapping' => [1, 10, 5, 20];
        yield 'touching' => [1, 5, 5, 9];
        yield 'disjoint' => [1, 3, 4, 6];
        yield 'nested' => [1, 100, 20, 30];
        yield 'identical' => [3, 7, 3, 7];
    }
}
