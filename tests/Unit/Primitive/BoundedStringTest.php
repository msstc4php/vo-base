<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidBoundedStringException;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BoundedString::class)]
final class BoundedStringTest extends TestCase
{
    public function testBelowMinimumLengthThrows(): void
    {
        $this->expectException(InvalidBoundedStringException::class);

        new BoundedString('ab', 3);
    }

    public function testAboveMaximumLengthThrows(): void
    {
        $this->expectException(InvalidBoundedStringException::class);

        new BoundedString('abcd', 0, 3);
    }

    public function testBoundaryLengthsConstruct(): void
    {
        self::assertSame('abc', new BoundedString('abc', 3, 3)->value);
        self::assertSame('', new BoundedString('', 0, 0)->value);
    }

    public function testMultibyteStringCountsCharactersNotBytes(): void
    {
        $string = new BoundedString('héllo', 5, 5);

        self::assertSame('héllo', $string->value);
    }

    public function testEqualsDistinguishesBounds(): void
    {
        self::assertTrue(new BoundedString('abc', 1, 5)->equals(new BoundedString('abc', 1, 5)));
        self::assertFalse(new BoundedString('abc', 1, 5)->equals(new BoundedString('abc', 1, 6)));
        self::assertFalse(new BoundedString('abc', 1, 5)->equals(new BoundedString('abc', 0, 5)));
    }
}
