<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Bridge\Doctrine\Odm;

use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\AbstractStringVoType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\ClassStringType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\DomainNameType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\EmailAddressType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\GeoCoordinatesType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\HexColorType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\HostnameType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\IpAddressType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\Ipv4AddressType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\Ipv6AddressType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\LocaleType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\MacAddressType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\PhoneNumberType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\PortType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\SemVerType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\SlugType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\TypeRegistrar;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\UriType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\UrlPathType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\UrlSchemeType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\UrlType;
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\UuidType;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Format\ClassString;
use MSSTC4PHP\ValueObject\Format\HexColor;
use MSSTC4PHP\ValueObject\Format\PhoneNumber;
use MSSTC4PHP\ValueObject\Format\SemVer;
use MSSTC4PHP\ValueObject\Format\Slug;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use MSSTC4PHP\ValueObject\I18n\Locale;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Identity\Uuid;
use MSSTC4PHP\ValueObject\Network\DomainName;
use MSSTC4PHP\ValueObject\Network\Hostname;
use MSSTC4PHP\ValueObject\Network\IpAddress;
use MSSTC4PHP\ValueObject\Network\Ipv4Address;
use MSSTC4PHP\ValueObject\Network\Ipv6Address;
use MSSTC4PHP\ValueObject\Network\MacAddress;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Network\Uri;
use MSSTC4PHP\ValueObject\Network\Url;
use MSSTC4PHP\ValueObject\Network\UrlPath;
use MSSTC4PHP\ValueObject\Network\UrlScheme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(AbstractStringVoType::class)]
#[CoversClass(ClassStringType::class)]
#[CoversClass(DomainNameType::class)]
#[CoversClass(EmailAddressType::class)]
#[CoversClass(GeoCoordinatesType::class)]
#[CoversClass(HexColorType::class)]
#[CoversClass(HostnameType::class)]
#[CoversClass(IpAddressType::class)]
#[CoversClass(Ipv4AddressType::class)]
#[CoversClass(Ipv6AddressType::class)]
#[CoversClass(LocaleType::class)]
#[CoversClass(MacAddressType::class)]
#[CoversClass(PhoneNumberType::class)]
#[CoversClass(PortType::class)]
#[CoversClass(SemVerType::class)]
#[CoversClass(SlugType::class)]
#[CoversClass(TypeRegistrar::class)]
#[CoversClass(UriType::class)]
#[CoversClass(UrlPathType::class)]
#[CoversClass(UrlSchemeType::class)]
#[CoversClass(UrlType::class)]
#[CoversClass(UuidType::class)]
final class AllTypesRoundTripTest extends TestCase
{
    protected function setUp(): void
    {
        TypeRegistrar::registerAll();
    }

    /**
     * @return iterable<string, array{string, ValueObject}>
     */
    public static function everyType(): iterable
    {
        yield 'email' => [EmailAddressType::NAME, EmailAddress::from('user@example.com')];
        yield 'uuid' => [UuidType::NAME, Uuid::from('018f8c1e-7b2a-7cde-8f01-2a3b4c5d6e7f')];
        yield 'ip' => [IpAddressType::NAME, IpAddress::fromString('192.168.0.1')];
        yield 'ipv4' => [Ipv4AddressType::NAME, Ipv4Address::from('192.168.0.1')];
        yield 'ipv6' => [Ipv6AddressType::NAME, Ipv6Address::from('::1')];
        yield 'mac' => [MacAddressType::NAME, MacAddress::from('aa:bb:cc:dd:ee:ff')];
        yield 'hostname' => [HostnameType::NAME, Hostname::from('example.com')];
        yield 'domain' => [DomainNameType::NAME, DomainName::from('example.com')];
        yield 'url' => [UrlType::NAME, Url::from('https://example.com/a')];
        yield 'uri' => [UriType::NAME, Uri::from('https://example.com/a?b=1')];
        yield 'url_scheme' => [UrlSchemeType::NAME, UrlScheme::from('https')];
        yield 'url_path' => [UrlPathType::NAME, UrlPath::from('/a/b')];
        yield 'slug' => [SlugType::NAME, Slug::from('my-slug')];
        yield 'phone' => [PhoneNumberType::NAME, PhoneNumber::from('+14155552671')];
        yield 'semver' => [SemVerType::NAME, SemVer::from('1.2.3')];
        yield 'hex_color' => [HexColorType::NAME, HexColor::from('#aabbcc')];
        yield 'class_string' => [ClassStringType::NAME, ClassString::from(RuntimeException::class)];
        yield 'locale' => [LocaleType::NAME, Locale::from('en-US')];
        yield 'port' => [PortType::NAME, Port::from(8080)];
        yield 'geo' => [GeoCoordinatesType::NAME, GeoCoordinates::fromFloats(51.5, -0.12)];
    }

    #[DataProvider('everyType')]
    public function testRoundTripsEveryType(string $typeName, ValueObject $vo): void
    {
        $type = Type::getType($typeName);

        $db = $type->convertToDatabaseValue($vo);
        self::assertNotNull($db);

        $restored = $type->convertToPHPValue($db);
        self::assertInstanceOf(ValueObject::class, $restored);
        self::assertTrue($vo->equals($restored));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nullableConverterKinds(): iterable
    {
        yield 'string vo' => [EmailAddressType::NAME];
        yield 'integer' => [PortType::NAME];
        yield 'array' => [GeoCoordinatesType::NAME];
    }

    #[DataProvider('nullableConverterKinds')]
    public function testNullRoundTrips(string $typeName): void
    {
        $type = Type::getType($typeName);

        self::assertNull($type->convertToDatabaseValue(null));
        self::assertNull($type->convertToPHPValue(null));
    }

    #[DataProvider('nullableConverterKinds')]
    public function testWrongPhpValueThrowsOnDatabaseConversion(string $typeName): void
    {
        $this->expectException(InvalidArgumentException::class);

        Type::getType($typeName)->convertToDatabaseValue('not-a-vo');
    }

    public function testStringConverterRejectsNonStringDatabaseValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Type::getType(EmailAddressType::NAME)->convertToPHPValue(123);
    }

    public function testPortConverterRejectsNonNumericDatabaseValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Type::getType(PortType::NAME)->convertToPHPValue('not-a-number');
    }

    public function testGeoConverterRejectsNonArrayDatabaseValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Type::getType(GeoCoordinatesType::NAME)->convertToPHPValue('not-an-array');
    }
}
