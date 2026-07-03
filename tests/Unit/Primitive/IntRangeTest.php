<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidIntRangeException;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
}
