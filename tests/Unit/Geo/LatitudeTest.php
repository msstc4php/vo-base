<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Geo;

use MSSTC4PHP\ValueObject\Exception\InvalidLatitudeException;
use MSSTC4PHP\ValueObject\Geo\Latitude;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Latitude::class)]
final class LatitudeTest extends TestCase
{
    public function testConstructsWithinBounds(): void
    {
        self::assertSame(-90.0, Latitude::from(-90.0)->value);
        self::assertSame(0.0, Latitude::from(0.0)->value);
        self::assertSame(90.0, Latitude::from(90.0)->value);
    }

    public function testRejectsAboveMaximum(): void
    {
        self::assertNull(Latitude::tryFrom(90.0001));
    }

    public function testRejectsBelowMinimum(): void
    {
        $this->expectException(InvalidLatitudeException::class);

        new Latitude(-91.0);
    }
}
