<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use DateTimeImmutable;
use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidBoundedStringException;
use MSSTC4PHP\ValueObject\Exception\InvalidClassStringException;
use MSSTC4PHP\ValueObject\Exception\InvalidDateRangeException;
use MSSTC4PHP\ValueObject\Exception\InvalidIntRangeException;
use MSSTC4PHP\ValueObject\Exception\InvalidLatitudeException;
use MSSTC4PHP\ValueObject\Exception\InvalidUuidException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Format\ClassString;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use MSSTC4PHP\ValueObject\Geo\Latitude;
use MSSTC4PHP\ValueObject\Geo\Longitude;
use MSSTC4PHP\ValueObject\Identity\Uuid;
use MSSTC4PHP\ValueObject\Network\QueryParameter;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use MSSTC4PHP\ValueObject\Support\AbstractFloatValueObject;
use MSSTC4PHP\ValueObject\Support\FreshInstance;
use MSSTC4PHP\ValueObject\Temporal\DateRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(BoundedString::class)]
#[CoversClass(ClassString::class)]
#[CoversClass(DateRange::class)]
#[CoversClass(GeoCoordinates::class)]
#[CoversClass(IntRange::class)]
#[CoversClass(QueryParameter::class)]
#[CoversClass(Uuid::class)]
#[UsesClass(AbstractFloatValueObject::class)]
#[CoversClass(FreshInstance::class)]
#[UsesClass(InvalidValueObjectException::class)]
#[UsesClass(Latitude::class)]
#[UsesClass(Longitude::class)]
final class CompositeSerializationTest extends TestCase
{
    /**
     * Payloads written by 1.3.0, which used PHP's default serialization.
     *
     * @return iterable<string, array{ValueObject, string}>
     */
    public static function legacyPayloads(): iterable
    {
        $start = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $end = new DateTimeImmutable('2026-12-31T00:00:00+00:00');

        yield 'BoundedString' => [new BoundedString('ab', 1, 3), self::payload(BoundedString::class, 's:5:"value";s:2:"ab";s:9:"minLength";i:1;s:9:"maxLength";i:3;', 3)];
        yield 'BoundedString without max' => [new BoundedString('ab'), self::payload(BoundedString::class, 's:5:"value";s:2:"ab";s:9:"minLength";i:0;s:9:"maxLength";N;', 3)];
        yield 'ClassString' => [new ClassString(stdClass::class), self::payload(ClassString::class, 's:5:"value";s:8:"stdClass";')];
        yield 'DateRange' => [new DateRange($start, $end), self::payload(DateRange::class, 's:5:"start";' . \serialize($start) . 's:3:"end";' . \serialize($end), 2)];
        yield 'GeoCoordinates' => [GeoCoordinates::fromFloats(1.5, 2.5), self::payload(GeoCoordinates::class, 's:8:"latitude";' . \serialize(new Latitude(1.5)) . 's:9:"longitude";' . \serialize(new Longitude(2.5)), 2)];
        yield 'IntRange' => [new IntRange(1, 2), self::payload(IntRange::class, 's:3:"min";i:1;s:3:"max";i:2;', 2)];
        yield 'QueryParameter' => [new QueryParameter('a', 'b'), self::payload(QueryParameter::class, 's:4:"name";s:1:"a";s:5:"value";s:1:"b";', 2)];
        yield 'Uuid' => [new Uuid('0190b1a2-0000-7000-8000-000000000000'), self::payload(Uuid::class, 's:5:"value";s:36:"0190b1a2-0000-7000-8000-000000000000";')];
    }

    #[DataProvider('legacyPayloads')]
    public function testSerializationKeepsThe130PayloadShape(ValueObject $original, string $payload): void
    {
        self::assertSame($payload, \serialize($original));
        self::assertTrue($original->equals(self::restored($payload)));
    }

    #[DataProvider('legacyPayloads')]
    public function testExtraPayloadKeysAreIgnored(ValueObject $original, string $payload): void
    {
        $withExtra = \preg_replace_callback('/:(\d+):\{/', static fn (array $m): string => ':' . ((int) $m[1] + 1) . ':{s:5:"extra";i:1;', $payload, 1);
        self::assertIsString($withExtra);

        self::assertTrue($original->equals(self::restored($withExtra)));
    }

    /**
     * @return iterable<string, array{string, class-string<InvalidValueObjectException>}>
     */
    public static function forgedInvalidPayloads(): iterable
    {
        yield 'BoundedString too long' => [self::payload(BoundedString::class, 's:5:"value";s:4:"abcd";s:9:"minLength";i:1;s:9:"maxLength";i:3;', 3), InvalidBoundedStringException::class];
        yield 'BoundedString negative min' => [self::payload(BoundedString::class, 's:5:"value";s:2:"ab";s:9:"minLength";i:-1;s:9:"maxLength";N;', 3), InvalidBoundedStringException::class];
        yield 'BoundedString max below min' => [self::payload(BoundedString::class, 's:5:"value";s:2:"ab";s:9:"minLength";i:3;s:9:"maxLength";i:2;', 3), InvalidBoundedStringException::class];
        yield 'ClassString unknown' => [self::payload(ClassString::class, 's:5:"value";s:9:"No\Such\X";'), InvalidClassStringException::class];
        yield 'DateRange reversed' => [self::payload(DateRange::class, 's:5:"start";' . \serialize(new DateTimeImmutable('2026-12-31')) . 's:3:"end";' . \serialize(new DateTimeImmutable('2026-01-01')), 2), InvalidDateRangeException::class];
        yield 'GeoCoordinates latitude out of range' => [self::payload(GeoCoordinates::class, 's:8:"latitude";' . self::payload(Latitude::class, 's:5:"value";d:91;') . 's:9:"longitude";' . \serialize(new Longitude(0.0)), 2), InvalidLatitudeException::class];
        yield 'IntRange reversed' => [self::payload(IntRange::class, 's:3:"min";i:5;s:3:"max";i:1;', 2), InvalidIntRangeException::class];
        yield 'Uuid not canonical' => [self::payload(Uuid::class, 's:5:"value";s:3:"zzz";'), InvalidUuidException::class];
    }

    /**
     * @param class-string<InvalidValueObjectException> $exception
     */
    #[DataProvider('forgedInvalidPayloads')]
    public function testAForgedInvalidPayloadIsRefused(string $payload, string $exception): void
    {
        $this->expectException($exception);

        \unserialize($payload);
    }

    public function testUuidStoresTheCanonicalForm(): void
    {
        $uuid = self::restored(self::payload(Uuid::class, 's:5:"value";s:36:"0190B1A2-0000-7000-8000-00000000000A";'));

        self::assertInstanceOf(Uuid::class, $uuid);
        self::assertSame('0190b1a2-0000-7000-8000-00000000000a', $uuid->value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function malformedPayloads(): iterable
    {
        $range = 'A serialized ' . IntRange::class . ' must hold min and max as ints.';
        $bounded = 'A serialized ' . BoundedString::class . ' must hold a string value, an int minLength and an int or null maxLength.';
        $dates = 'A serialized ' . DateRange::class . ' must hold start and end as DateTimeImmutable.';
        $geo = 'A serialized ' . GeoCoordinates::class . ' must hold a Latitude and a Longitude.';
        $query = 'A serialized ' . QueryParameter::class . ' must hold name and value as strings.';

        yield 'IntRange missing max' => [self::payload(IntRange::class, 's:3:"min";i:1;'), $range];
        yield 'IntRange string min' => [self::payload(IntRange::class, 's:3:"min";s:6:"secret";s:3:"max";i:2;', 2), $range];
        yield 'IntRange string max' => [self::payload(IntRange::class, 's:3:"min";i:1;s:3:"max";s:6:"secret";', 2), $range];
        yield 'BoundedString int value' => [self::payload(BoundedString::class, 's:5:"value";i:7;s:9:"minLength";i:0;s:9:"maxLength";N;', 3), $bounded];
        yield 'BoundedString missing min' => [self::payload(BoundedString::class, 's:5:"value";s:6:"secret";s:9:"maxLength";N;', 2), $bounded];
        yield 'BoundedString missing max' => [self::payload(BoundedString::class, 's:5:"value";s:6:"secret";s:9:"minLength";i:0;', 2), $bounded];
        yield 'BoundedString string max' => [self::payload(BoundedString::class, 's:5:"value";s:6:"secret";s:9:"minLength";i:0;s:9:"maxLength";s:1:"3";', 3), $bounded];
        yield 'DateRange missing end' => [self::payload(DateRange::class, 's:5:"start";' . \serialize(new DateTimeImmutable('2026-01-01'))), $dates];
        yield 'DateRange string start' => [self::payload(DateRange::class, 's:5:"start";s:6:"secret";s:3:"end";' . \serialize(new DateTimeImmutable('2026-01-01')), 2), $dates];
        yield 'GeoCoordinates missing longitude' => [self::payload(GeoCoordinates::class, 's:8:"latitude";' . \serialize(new Latitude(1.0))), $geo];
        yield 'GeoCoordinates swapped' => [self::payload(GeoCoordinates::class, 's:8:"latitude";' . \serialize(new Longitude(1.0)) . 's:9:"longitude";' . \serialize(new Latitude(1.0)), 2), $geo];
        yield 'GeoCoordinates float latitude' => [self::payload(GeoCoordinates::class, 's:8:"latitude";d:1;s:9:"longitude";' . \serialize(new Longitude(1.0)), 2), $geo];
        yield 'QueryParameter int name' => [self::payload(QueryParameter::class, 's:4:"name";i:1;s:5:"value";s:6:"secret";', 2), $query];
        yield 'QueryParameter missing value' => [self::payload(QueryParameter::class, 's:4:"name";s:6:"secret";'), $query];
        yield 'Uuid int value' => [self::payload(Uuid::class, 's:5:"value";i:42;'), 'A serialized ' . Uuid::class . ' must hold its value as a string.'];
        yield 'ClassString null value' => [self::payload(ClassString::class, 's:5:"value";N;'), 'A serialized ' . ClassString::class . ' must hold its value as a string.'];
    }

    #[DataProvider('malformedPayloads')]
    public function testAMalformedPayloadIsRefusedWithoutEchoingIt(string $payload, string $message): void
    {
        $this->expectExceptionObject(new InvalidValueObjectException($message));

        \unserialize($payload);
    }

    /**
     * @return iterable<string, array{ValueObject, array<string, scalar|object|null>}>
     */
    public static function heldValues(): iterable
    {
        $date = new DateTimeImmutable('2026-01-01');

        yield 'BoundedString' => [new BoundedString('ab'), ['value' => 'cd', 'minLength' => 0, 'maxLength' => null]];
        yield 'ClassString' => [new ClassString(stdClass::class), ['value' => self::class]];
        yield 'DateRange' => [new DateRange($date, $date), ['start' => $date, 'end' => $date]];
        yield 'GeoCoordinates' => [GeoCoordinates::fromFloats(1.0, 2.0), ['latitude' => new Latitude(3.0), 'longitude' => new Longitude(4.0)]];
        yield 'IntRange' => [new IntRange(1, 2), ['min' => 3, 'max' => 4]];
        yield 'QueryParameter' => [new QueryParameter('a', 'b'), ['name' => 'c', 'value' => 'd']];
        yield 'Uuid' => [new Uuid('0190b1a2-0000-7000-8000-000000000000'), ['value' => '0190b1a2-0000-7000-8000-000000000001']];
    }

    /**
     * @param array<string, scalar|object|null> $data
     */
    #[DataProvider('heldValues')]
    public function testUnserializeRefusesAnInstanceThatHoldsAValue(ValueObject $object, array $data): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A value object is immutable; __unserialize() runs only on a new object.');

        self::assertTrue(\method_exists($object, '__unserialize'));
        $object->__unserialize($data);
    }

    /**
     * @param class-string $class
     */
    private static function payload(string $class, string $body, int $count = 1): string
    {
        return \sprintf('O:%d:"%s":%d:{%s}', \strlen($class), $class, $count, $body);
    }

    private static function restored(string $payload): ValueObject
    {
        $object = \unserialize($payload);
        self::assertInstanceOf(ValueObject::class, $object);

        return $object;
    }
}
