<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\AbstractIntValueObject;
use MSSTC4PHP\ValueObject\Tests\Fixtures\BoundedNumber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractIntValueObject::class)]
final class AbstractIntValueObjectTest extends TestCase
{
    public function testConstructsWithinBounds(): void
    {
        self::assertSame(0, BoundedNumber::from(0)->value);
        self::assertSame(10, BoundedNumber::from(10)->value);
        self::assertSame(5, BoundedNumber::from(5)->jsonSerialize());
    }

    public function testRejectsOutOfBounds(): void
    {
        self::assertNull(BoundedNumber::tryFrom(-1));
        self::assertNull(BoundedNumber::tryFrom(11));
        self::assertFalse(BoundedNumber::isValid(11));
    }

    public function testThrowsTypedExceptionOnInvalidInput(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        BoundedNumber::from(11);
    }

    public function testEquals(): void
    {
        self::assertTrue(BoundedNumber::from(3)->equals(BoundedNumber::from(3)));
        self::assertFalse(BoundedNumber::from(3)->equals(BoundedNumber::from(4)));
    }

    public function testCompareToOrdersByValue(): void
    {
        self::assertSame(-1, BoundedNumber::from(1)->compareTo(BoundedNumber::from(2)));
        self::assertSame(0, BoundedNumber::from(2)->compareTo(BoundedNumber::from(2)));
        self::assertSame(1, BoundedNumber::from(3)->compareTo(BoundedNumber::from(2)));
    }

    public function testStringForm(): void
    {
        self::assertSame('7', (string) BoundedNumber::from(7));
    }
}
