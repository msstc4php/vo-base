<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidDecimalException;
use MSSTC4PHP\ValueObject\Exception\InvalidPercentageException;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Percentage::class)]
final class PercentageTest extends TestCase
{
    public function testOutOfRangeValuesAreRejected(): void
    {
        self::assertNull(Percentage::tryFrom(-0.1));
        self::assertNull(Percentage::tryFrom(100.1));
    }

    public function testBelowZeroThrowsTypedException(): void
    {
        $this->expectException(InvalidPercentageException::class);

        Percentage::from(-0.1);
    }

    public function testBoundaryAndInteriorValuesConstruct(): void
    {
        self::assertSame(0.0, Percentage::from(0.0)->value);
        self::assertSame(50.0, Percentage::from(50.0)->value);
        self::assertSame(100.0, Percentage::from(100.0)->value);
    }

    public function testEqualsComparesValue(): void
    {
        self::assertTrue(Percentage::from(50.0)->equals(Percentage::from(50.0)));
        self::assertFalse(Percentage::from(50.0)->equals(Percentage::from(75.0)));
    }

    public function testApplyToAndOf(): void
    {
        self::assertSame('30', Percentage::from(20.0)->applyTo('150')->value);
        self::assertSame('30', Percentage::from(20.0)->of('150')->value);
        self::assertSame('0', Percentage::from(20.0)->applyTo('0')->value);
    }

    public function testFromRatio(): void
    {
        self::assertSame(25.0, Percentage::fromRatio(1, 4)->value);
        self::assertSame(50.0, Percentage::fromRatio(1, 2)->value);
    }

    public function testFromRatioDivideByZeroThrows(): void
    {
        $this->expectException(InvalidDecimalException::class);

        Percentage::fromRatio(1, 0);
    }
}
