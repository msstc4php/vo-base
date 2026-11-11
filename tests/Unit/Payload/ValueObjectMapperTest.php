<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload;

use BackedEnum;
use DateTimeImmutable;
use Generator;
use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidEmailAddressException;
use MSSTC4PHP\ValueObject\Exception\InvalidPayloadException;
use MSSTC4PHP\ValueObject\Exception\InvalidPortException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use MSSTC4PHP\ValueObject\Geo\Latitude;
use MSSTC4PHP\ValueObject\Geo\Longitude;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Network\QueryParameter;
use MSSTC4PHP\ValueObject\Payload\BoundedStringCodec;
use MSSTC4PHP\ValueObject\Payload\DateRangeCodec;
use MSSTC4PHP\ValueObject\Payload\GeoCoordinatesCodec;
use MSSTC4PHP\ValueObject\Payload\IntRangeCodec;
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use MSSTC4PHP\ValueObject\Support\AbstractFloatValueObject;
use MSSTC4PHP\ValueObject\Support\AbstractIntValueObject;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Temporal\DateRange;
use MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture\LyingCodec;
use MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture\Priority;
use MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture\RangeAsTextCodec;
use MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture\StringOnly;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ValueObjectMapper::class)]
#[UsesClass(BoundedStringCodec::class)]
#[UsesClass(IntRangeCodec::class)]
#[UsesClass(GeoCoordinatesCodec::class)]
#[UsesClass(DateRangeCodec::class)]
#[UsesClass(InvalidValueObjectException::class)]
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
#[UsesClass(InvalidPayloadException::class)]
final class ValueObjectMapperTest extends TestCase
{
    private ValueObjectMapper $mapper;

    #[Override]
    protected function setUp(): void
    {
        $this->mapper = new ValueObjectMapper();
    }

    /**
     * @return iterable<string, array{ValueObject|BackedEnum, string|int|float|array<string, scalar|null>}>
     */
    public static function roundTrips(): iterable
    {
        yield 'string-backed' => [EmailAddress::from('a@example.com'), 'a@example.com'];
        yield 'int-backed' => [Port::from(8080), 8080];
        yield 'float-backed' => [Percentage::from(12.5), 12.5];
        yield 'backed enum' => [CountryCode::DE, 'DE'];
        yield 'composite' => [new IntRange(1, 5), ['min' => 1, 'max' => 5]];
        yield 'composite keeping its bounds' => [new BoundedString('ab', 1, 3), ['value' => 'ab', 'minLength' => 1, 'maxLength' => 3]];
    }

    /**
     * @param string|int|float|array<string, scalar|null> $payload
     */
    #[DataProvider('roundTrips')]
    public function testRoundTrip(ValueObject|BackedEnum $value, string|int|float|array $payload): void
    {
        self::assertSame($payload, $this->mapper->toPayload($value));
        self::assertEquals($value, $this->mapper->fromPayload($value::class, $payload));
    }

    public function testDatabaseStringsAreReadAsNumbers(): void
    {
        self::assertSame(443, $this->mapper->fromPayload(Port::class, '443')->value);
        self::assertSame(-12.5, $this->mapper->fromPayload(Latitude::class, '-12.5')->value);
        self::assertSame(12.0, $this->mapper->fromPayload(Percentage::class, 12)->value);
        self::assertSame(Priority::High, $this->mapper->fromPayload(Priority::class, '2'));
        self::assertSame(Priority::Low, $this->mapper->fromPayload(Priority::class, 1));
        self::assertSame(2, $this->mapper->toPayload(Priority::High));
    }

    public function testDateRangeAndGeoCoordinatesComeBackEqual(): void
    {
        $range = new DateRange(new DateTimeImmutable('2026-01-01T00:00:00.5+02:00'), new DateTimeImmutable('2026-02-01T00:00:00Z'));
        $point = GeoCoordinates::fromFloats(55.75, 37.61);

        self::assertTrue($range->equals($this->mapper->fromPayload(DateRange::class, $this->mapper->toPayload($range))));
        self::assertTrue($point->equals($this->mapper->fromPayload(GeoCoordinates::class, $this->mapper->toPayload($point))));
    }

    /**
     * @return iterable<string, array{class-string, mixed}>
     */
    public static function wrongTypes(): iterable
    {
        yield 'int for a string-backed' => [EmailAddress::class, 5];
        yield 'non-digit string for an int-backed' => [Port::class, '80a'];
        yield 'float for an int-backed' => [Port::class, 80.0];
        yield 'int-overflowing digits' => [Port::class, '99999999999999999999'];
        yield 'digits with a trailing newline' => [Port::class, "80\n"];
        yield 'digits with a prefix' => [Port::class, 'a80'];
        yield 'digits with a leading zero' => [Port::class, '080'];
        yield 'exponent for an int-backed' => [Port::class, '8e1'];
        yield 'padded number for a float-backed' => [Percentage::class, ' 1.5'];
        yield 'leading zero for a float-backed' => [Percentage::class, '007.5'];
        yield 'infinite number for a float-backed' => [Percentage::class, '1e999'];
        yield 'leading zero for an int-backed enum' => [Priority::class, '02'];
        yield 'string for an int-backed enum' => [Priority::class, 'high'];
        yield 'digits with a trailing newline for an int-backed enum' => [Priority::class, "2\n"];
        yield 'non-numeric string for a float-backed' => [Percentage::class, 'abc'];
        yield 'int for a string-backed enum' => [CountryCode::class, 1];
        yield 'unknown enum case' => [CountryCode::class, 'XX'];
        yield 'string for a composite' => [IntRange::class, '{"min":1,"max":2}'];
        yield 'malformed composite' => [IntRange::class, ['min' => 1]];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('wrongTypes')]
    public function testWrongTypeOrShapeIsAPayloadError(string $class, mixed $payload): void
    {
        $this->expectException(InvalidPayloadException::class);

        $this->mapper->fromPayload($class, $payload);
    }

    public function testInvalidContentKeepsTheValueObjectsOwnException(): void
    {
        try {
            $this->mapper->fromPayload(EmailAddress::class, 'not an email');
            self::fail('Expected an exception.');
        } catch (InvalidValueObjectException $exception) {
            self::assertInstanceOf(InvalidEmailAddressException::class, $exception);
        }

        $this->expectException(InvalidPortException::class);
        $this->mapper->fromPayload(Port::class, '70000');
    }

    public function testUnsupportedClassesAreAProgrammerError(): void
    {
        self::assertFalse($this->mapper->supports(stdClass::class));
        self::assertFalse($this->mapper->supports(QueryParameter::class));
        self::assertFalse($this->mapper->supports('No\Such\Class'));

        $this->expectException(LogicException::class);
        $this->mapper->fromPayload(stdClass::class, 'x');
    }

    public function testUnsupportedValueIsAProgrammerError(): void
    {
        $this->expectException(LogicException::class);
        $this->mapper->toPayload(new QueryParameter('a', 'b'));
    }

    public function testEveryKindIsSupported(): void
    {
        foreach ([EmailAddress::class, Port::class, Percentage::class, CountryCode::class, IntRange::class, BoundedString::class, GeoCoordinates::class, DateRange::class] as $class) {
            self::assertTrue($this->mapper->supports($class), $class);
        }
    }

    public function testACodecRejectsAForeignValueObject(): void
    {
        $this->expectException(LogicException::class);
        new IntRangeCodec()->encode(EmailAddress::from('a@example.com'));
    }

    public function testTrustedSourceOnlyClassesAreRefusedOnTheUntrustedPath(): void
    {
        self::assertTrue($this->mapper->supports(BoundedString::class));
        self::assertFalse($this->mapper->supportsUntrusted(BoundedString::class));
        self::assertTrue($this->mapper->supportsUntrusted(IntRange::class));
        self::assertTrue($this->mapper->supportsUntrusted(EmailAddress::class));
        self::assertFalse($this->mapper->supportsUntrusted(stdClass::class));
        self::assertEquals(new IntRange(1, 2), $this->mapper->fromUntrustedPayload(IntRange::class, ['min' => 1, 'max' => 2]));

        $this->expectException(InvalidPayloadException::class);
        $this->mapper->fromUntrustedPayload(BoundedString::class, ['value' => 'x', 'minLength' => 0, 'maxLength' => null]);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function scalarBackedness(): iterable
    {
        yield 'string-backed' => [EmailAddress::class, true];
        yield 'int-backed' => [Port::class, true];
        yield 'float-backed' => [Percentage::class, true];
        yield 'enum' => [CountryCode::class, false];
        yield 'composite' => [IntRange::class, false];
        yield 'stringable composite' => [BoundedString::class, false];
    }

    public function testAnUnsupportedClassOnTheUntrustedPathIsStillAProgrammerError(): void
    {
        $this->expectException(LogicException::class);
        $this->mapper->fromUntrustedPayload(stdClass::class, 'x');
    }

    public function testCompositesAreTheClassesWithACodec(): void
    {
        self::assertTrue($this->mapper->isComposite(IntRange::class));
        self::assertTrue($this->mapper->isComposite(BoundedString::class));
        self::assertFalse($this->mapper->isComposite(EmailAddress::class));
        self::assertFalse($this->mapper->isComposite(CountryCode::class));
    }

    #[DataProvider('scalarBackedness')]
    public function testScalarBackedMeansOneStringOrNumberPayload(string $class, bool $expected): void
    {
        self::assertSame($expected, $this->mapper->isScalarBacked($class));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function payloadTypes(): iterable
    {
        yield 'string-backed' => [EmailAddress::class, 'string'];
        yield 'int-backed' => [Port::class, 'int'];
        yield 'float-backed' => [Percentage::class, 'float'];
        yield 'string enum' => [CountryCode::class, 'string'];
        yield 'int enum' => [Priority::class, 'int'];
        yield 'composite' => [IntRange::class, 'array'];
        yield 'stringable composite' => [BoundedString::class, 'array'];
    }

    #[DataProvider('payloadTypes')]
    public function testPayloadTypeIsTheJsonTypeOfThePayload(string $class, string $expected): void
    {
        self::assertSame($expected, $this->mapper->payloadType($class));
    }

    public function testPayloadTypeOfAnUnsupportedClassIsAProgrammerError(): void
    {
        $this->expectException(LogicException::class);
        $this->mapper->payloadType(stdClass::class);
    }

    public function testAStringConvertibleThatIsNoValueObjectIsNotStored(): void
    {
        self::assertFalse($this->mapper->supports(StringOnly::class));

        $this->expectException(LogicException::class);
        $this->mapper->fromPayload(StringOnly::class, 'x');
    }

    public function testACodecDecodingIntoAnotherClassIsAProgrammerError(): void
    {
        $mapper = new ValueObjectMapper([new LyingCodec(EmailAddress::from('a@example.com'))]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The payload codec for ' . IntRange::class . ' returned ' . EmailAddress::class . '.');
        $mapper->fromPayload(IntRange::class, []);
    }

    public function testCodecsArriveFromAnyIterable(): void
    {
        $mapper = new ValueObjectMapper((static function (): Generator {
            yield 'custom' => new RangeAsTextCodec();
        })());

        self::assertSame(['range' => '1..2'], $mapper->toPayload(new IntRange(1, 2)));
    }

    public function testACustomCodecReplacesTheBuiltInOne(): void
    {
        $codec = new RangeAsTextCodec();

        $mapper = new ValueObjectMapper([$codec]);

        self::assertSame(['range' => '1..2'], $mapper->toPayload(new IntRange(1, 2)));
        self::assertTrue(new IntRange(1, 2)->equals($mapper->fromPayload(IntRange::class, ['range' => '1..2'])));
    }
}
