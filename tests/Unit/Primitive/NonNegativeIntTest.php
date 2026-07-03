<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidNonNegativeIntException;
use MSSTC4PHP\ValueObject\Primitive\NonNegativeInt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NonNegativeInt::class)]
final class NonNegativeIntTest extends TestCase
{
    public function testNegativeValueIsRejected(): void
    {
        self::assertNull(NonNegativeInt::tryFrom(-1));
    }

    public function testNegativeValueThrowsTypedException(): void
    {
        $this->expectException(InvalidNonNegativeIntException::class);

        NonNegativeInt::from(-1);
    }

    public function testZeroConstructs(): void
    {
        self::assertSame(0, NonNegativeInt::from(0)->value);
    }
}
