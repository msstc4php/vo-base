<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use MSSTC4PHP\ValueObject\Support\AbstractFloatValueObject;
use MSSTC4PHP\ValueObject\Tests\Fixtures\UnitFraction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractFloatValueObject::class)]
final class AbstractFloatValueObjectTest extends TestCase
{
    public function testConstructsWithinBounds(): void
    {
        self::assertSame(0.0, UnitFraction::from(0.0)->value);
        self::assertSame(1.0, UnitFraction::from(1.0)->value);
    }

    public function testRejectsOutOfBounds(): void
    {
        self::assertNull(UnitFraction::tryFrom(-0.1));
        self::assertNull(UnitFraction::tryFrom(1.1));
    }

    public function testCompareToOrdersByValue(): void
    {
        self::assertSame(-1, UnitFraction::from(0.25)->compareTo(UnitFraction::from(0.75)));
        self::assertSame(0, UnitFraction::from(0.5)->compareTo(UnitFraction::from(0.5)));
    }

    public function testCompareToRejectsDifferentType(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        UnitFraction::from(0.5)->compareTo(Percentage::from(50.0));
    }

    public function testEqualsIsFalseForDifferentType(): void
    {
        self::assertFalse(UnitFraction::from(0.5)->equals(Percentage::from(50.0)));
    }

    public function testIsValidAndConversions(): void
    {
        self::assertTrue(UnitFraction::isValid(0.5));
        self::assertFalse(UnitFraction::isValid(2.0));
        self::assertSame('0.5', (string) UnitFraction::from(0.5));
        self::assertSame(0.5, UnitFraction::from(0.5)->jsonSerialize());
    }
}
