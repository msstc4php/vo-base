<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Geo;

use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use MSSTC4PHP\ValueObject\Geo\Latitude;
use MSSTC4PHP\ValueObject\Geo\Longitude;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GeoCoordinates::class)]
final class GeoCoordinatesTest extends TestCase
{
    public function testConstructsFromValueObjects(): void
    {
        $coordinates = new GeoCoordinates(new Latitude(51.5), new Longitude(-0.12));

        self::assertSame(51.5, $coordinates->latitude()->value);
        self::assertSame(-0.12, $coordinates->longitude()->value);
    }

    public function testConstructsFromFloats(): void
    {
        $coordinates = GeoCoordinates::fromFloats(48.85, 2.35);

        self::assertSame(48.85, $coordinates->latitude()->value);
        self::assertSame(2.35, $coordinates->longitude()->value);
    }

    public function testEqualsIsTrueForIdenticalPair(): void
    {
        self::assertTrue(
            GeoCoordinates::fromFloats(10.0, 20.0)->equals(GeoCoordinates::fromFloats(10.0, 20.0)),
        );
    }

    public function testEqualsIsFalseWhenLatitudeDiffers(): void
    {
        self::assertFalse(
            GeoCoordinates::fromFloats(10.0, 20.0)->equals(GeoCoordinates::fromFloats(11.0, 20.0)),
        );
    }

    public function testToArrayShape(): void
    {
        self::assertSame(
            ['lat' => 10.0, 'lng' => 20.0],
            GeoCoordinates::fromFloats(10.0, 20.0)->toArray(),
        );
    }

    public function testJsonSerializeShape(): void
    {
        self::assertSame(
            ['lat' => 10.0, 'lng' => 20.0],
            GeoCoordinates::fromFloats(10.0, 20.0)->jsonSerialize(),
        );
    }
}
