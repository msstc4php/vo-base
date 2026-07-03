<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Identity;

use MSSTC4PHP\ValueObject\Exception\InvalidUuidException;
use MSSTC4PHP\ValueObject\Identity\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Uuid::class)]
final class UuidTest extends TestCase
{
    private const string SAMPLE_V4 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

    public function testConstructsFromValidV4String(): void
    {
        $uuid = Uuid::from(self::SAMPLE_V4);

        self::assertSame(self::SAMPLE_V4, $uuid->value);
        self::assertSame(4, $uuid->version());
    }

    public function testStoresValueLowercased(): void
    {
        $uuid = Uuid::from(\strtoupper(self::SAMPLE_V4));

        self::assertSame(self::SAMPLE_V4, $uuid->value);
    }

    public function testRejectsMalformedLength(): void
    {
        $this->expectException(InvalidUuidException::class);

        Uuid::from('f47ac10b-58cc-4372-a567-0e02b2c3d4');
    }

    public function testRejectsNonHexCharacters(): void
    {
        $this->expectException(InvalidUuidException::class);

        Uuid::from('z47ac10b-58cc-4372-a567-0e02b2c3d479');
    }

    public function testV4HasVersionFourAndReParsesEqual(): void
    {
        $uuid = Uuid::v4();

        self::assertSame(4, $uuid->version());
        self::assertSame(1, $uuid->variant());
        self::assertTrue($uuid->equals(Uuid::from((string) $uuid)));
    }

    public function testV4ProducesDistinctValues(): void
    {
        self::assertNotSame(Uuid::v4()->value, Uuid::v4()->value);
    }

    public function testV7HasVersionSeven(): void
    {
        $uuid = Uuid::v7(1_700_000_000_000);

        self::assertSame(7, $uuid->version());
        self::assertSame(1, $uuid->variant());
    }

    public function testV7PreservesTimestampOrdering(): void
    {
        $earlier = Uuid::v7(1_700_000_000_000);
        $later = Uuid::v7(1_700_000_001_000);

        // The leading 48 timestamp bits map to the first 13 chars ("xxxxxxxx-xxxx").
        self::assertLessThan(\substr($later->value, 0, 13), \substr($earlier->value, 0, 13));
    }

    public function testV7RoundTrips(): void
    {
        $uuid = Uuid::v7(1_700_000_000_000);

        self::assertTrue($uuid->equals(Uuid::from((string) $uuid)));
    }
}
