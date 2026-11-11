<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidPayloadException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use MSSTC4PHP\ValueObject\Geo\Latitude;
use MSSTC4PHP\ValueObject\Geo\Longitude;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Network\QueryParameter;
use MSSTC4PHP\ValueObject\Payload\BoundedStringCodec;
use MSSTC4PHP\ValueObject\Payload\DateRangeCodec;
use MSSTC4PHP\ValueObject\Payload\GeoCoordinatesCodec;
use MSSTC4PHP\ValueObject\Payload\IntRangeCodec;
use MSSTC4PHP\ValueObject\Payload\PayloadCodec;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use MSSTC4PHP\ValueObject\Support\AbstractFloatValueObject;
use MSSTC4PHP\ValueObject\Support\AbstractIntValueObject;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Temporal\DateRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GeoCoordinatesCodec::class)]
#[CoversClass(DateRangeCodec::class)]
#[CoversClass(IntRangeCodec::class)]
#[CoversClass(BoundedStringCodec::class)]
#[UsesClass(AbstractFloatValueObject::class)]
#[UsesClass(AbstractStringValueObject::class)]
#[UsesClass(AbstractIntValueObject::class)]
#[UsesClass(Latitude::class)]
#[UsesClass(Longitude::class)]
#[UsesClass(GeoCoordinates::class)]
#[UsesClass(DateRange::class)]
#[UsesClass(IntRange::class)]
#[UsesClass(BoundedString::class)]
#[UsesClass(Percentage::class)]
#[UsesClass(Port::class)]
#[UsesClass(QueryParameter::class)]
#[UsesClass(EmailAddress::class)]
#[UsesClass(InvalidValueObjectException::class)]
#[UsesClass(InvalidPayloadException::class)]
final class PayloadCodecTest extends TestCase
{
    public function testGeoRoundTripAndStoredForm(): void
    {
        $codec = new GeoCoordinatesCodec();
        $value = GeoCoordinates::fromFloats(55.751244, 37.618423);

        self::assertSame(['lat' => 55.751244, 'lng' => 37.618423], $codec->encode($value));
        self::assertTrue($value->equals($codec->decode($codec->encode($value))));
        self::assertTrue(GeoCoordinates::fromFloats(-90.0, 180.0)->equals($codec->decode(['lat' => -90.0, 'lng' => 180.0])));
    }

    public function testDateRangeRoundTripAndStoredForm(): void
    {
        $codec = new DateRangeCodec();
        $value = new DateRange(
            new DateTimeImmutable('2026-11-07T10:00:00.123456+03:00'),
            new DateTimeImmutable('2026-11-08T10:00:00.654321+03:00'),
        );

        self::assertSame(
            ['start' => '2026-11-07T10:00:00.123456+03:00', 'end' => '2026-11-08T10:00:00.654321+03:00'],
            $codec->encode($value),
        );
        self::assertTrue($value->equals($codec->decode($codec->encode($value))));
    }

    public function testDateRangeAcceptsAZuluOffsetFromApiClients(): void
    {
        $range = new DateRangeCodec()->decode(['start' => '2026-01-01T00:00:00Z', 'end' => '2026-01-02T00:00:00.5Z']);

        self::assertSame('2026-01-01T00:00:00.000000+00:00', $range->start->format('Y-m-d\\TH:i:s.uP'));
        self::assertSame('2026-01-02T00:00:00.500000+00:00', $range->end->format('Y-m-d\\TH:i:s.uP'));
    }

    public function testDateRangeReadsRowsWrittenInAtomFormat(): void
    {
        $range = new DateRangeCodec()->decode(['start' => '2026-01-01T00:00:00+00:00', 'end' => '2026-01-02T00:00:00+00:00']);

        self::assertSame('2026-01-01T00:00:00+00:00', $range->start->format(\DATE_ATOM));
    }

    public function testIntRangeRoundTripAndStoredForm(): void
    {
        $codec = new IntRangeCodec();
        $value = new IntRange(-5, 5);

        self::assertSame(['min' => -5, 'max' => 5], $codec->encode($value));
        self::assertTrue($value->equals($codec->decode($codec->encode($value))));
    }

    public function testBoundedStringRoundTripAndStoredForm(): void
    {
        $codec = new BoundedStringCodec();

        foreach ([new BoundedString('abc', 1, 10), new BoundedString('abc', 0)] as $value) {
            self::assertSame(['value' => 'abc', 'minLength' => $value->minLength, 'maxLength' => $value->maxLength], $codec->encode($value));
            self::assertTrue($value->equals($codec->decode($codec->encode($value))));
        }
    }

    public function testCodecsNameTheirValueObjects(): void
    {
        self::assertSame(GeoCoordinates::class, new GeoCoordinatesCodec()->valueObjectClass());
        self::assertSame(DateRange::class, new DateRangeCodec()->valueObjectClass());
        self::assertSame(IntRange::class, new IntRangeCodec()->valueObjectClass());
        self::assertSame(BoundedString::class, new BoundedStringCodec()->valueObjectClass());
    }

    public function testGeoAcceptsIntegerCoordinates(): void
    {
        self::assertTrue(GeoCoordinates::fromFloats(10.0, 20.0)->equals(new GeoCoordinatesCodec()->decode(['lat' => 10, 'lng' => 20])));
    }

    /**
     * @return iterable<string, array{PayloadCodec<ValueObject>, array<array-key, mixed>}>
     */
    public static function malformedProvider(): iterable
    {
        yield 'geo missing lng' => [new GeoCoordinatesCodec(), ['lat' => 1.0]];
        yield 'geo string lat' => [new GeoCoordinatesCodec(), ['lat' => '1.0', 'lng' => 2.0]];
        yield 'date range missing end' => [new DateRangeCodec(), ['start' => '2026-01-01T00:00:00+00:00']];
        yield 'date range garbage instant' => [new DateRangeCodec(), ['start' => 'not a date', 'end' => '2026-01-01T00:00:00+00:00']];
        yield 'date range relative instant' => [new DateRangeCodec(), ['start' => 'now', 'end' => '+1 year']];
        yield 'date range without offset' => [new DateRangeCodec(), ['start' => '2026-01-01T00:00:00', 'end' => '2026-02-01T00:00:00']];
        yield 'date range overflowing month' => [new DateRangeCodec(), ['start' => '2026-13-01T00:00:00+00:00', 'end' => '2027-02-01T00:00:00+00:00']];
        yield 'int range string max' => [new IntRangeCodec(), ['min' => 1, 'max' => '2']];
        yield 'bounded string without minLength' => [new BoundedStringCodec(), ['value' => 'a']];
        yield 'bounded string float maxLength' => [new BoundedStringCodec(), ['value' => 'a', 'minLength' => 0, 'maxLength' => 1.5]];
        yield 'empty payload' => [new IntRangeCodec(), []];
    }

    /**
     * @param PayloadCodec<ValueObject> $codec
     * @param array<array-key, mixed> $payload
     */
    #[DataProvider('malformedProvider')]
    public function testMalformedPayloadIsRejected(PayloadCodec $codec, array $payload): void
    {
        $this->expectException(InvalidPayloadException::class);

        $codec->decode($payload);
    }
}
