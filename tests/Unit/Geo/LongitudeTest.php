<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Geo;

use MSSTC4PHP\ValueObject\Exception\InvalidLongitudeException;
use MSSTC4PHP\ValueObject\Geo\Longitude;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Longitude::class)]
final class LongitudeTest extends TestCase
{
    public function testConstructsWithinBounds(): void
    {
        self::assertSame(-180.0, Longitude::from(-180.0)->value);
        self::assertSame(180.0, Longitude::from(180.0)->value);
    }

    public function testRejectsAboveMaximum(): void
    {
        $this->expectException(InvalidLongitudeException::class);

        new Longitude(180.1);
    }
}
