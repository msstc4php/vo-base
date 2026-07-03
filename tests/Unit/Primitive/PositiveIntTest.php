<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidPositiveIntException;
use MSSTC4PHP\ValueObject\Primitive\PositiveInt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PositiveInt::class)]
final class PositiveIntTest extends TestCase
{
    public function testNonPositiveValuesAreRejected(): void
    {
        self::assertNull(PositiveInt::tryFrom(0));
        self::assertNull(PositiveInt::tryFrom(-1));
    }

    public function testZeroThrowsTypedException(): void
    {
        $this->expectException(InvalidPositiveIntException::class);

        PositiveInt::from(0);
    }

    public function testPositiveValueConstructs(): void
    {
        self::assertSame(1, PositiveInt::from(1)->value);
    }
}
