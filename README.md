# msstc4php/value-objects

Immutable, self-validating value objects for **PHP 8.4+**, with optional
bridges for **Doctrine ORM**, **Doctrine MongoDB ODM**, and **Symfony
Validator**. The core has **zero runtime dependencies** — the bridges load only
when their framework is installed.

## Install

```bash
composer require msstc4php/value-objects
```

Optional bridges (install the frameworks you use):

```bash
composer require doctrine/orm          # or doctrine/dbal
composer require doctrine/mongodb-odm
composer require symfony/validator
composer require brick/math            # arithmetic on Decimal and Percentage
```

## Design

Every value object is a `final readonly` class that validates in its
constructor and throws a typed exception on invalid input — there is no
partially-valid state. Validation lives in **one place** per type and is reused
by the Symfony bridge, so a value object and its constraint can never disagree.

```php
use MSSTC4PHP\ValueObject\Network\Ipv4Address;

$ip = Ipv4Address::from('192.168.0.1'); // throws InvalidIpv4Exception on bad input
Ipv4Address::tryFrom('nope');           // null instead of throwing
Ipv4Address::isValid('192.168.0.1');    // bool
(string) $ip;                            // '192.168.0.1'
$ip->equals(Ipv4Address::from('192.168.0.1')); // true
```

The construction convention mirrors `BackedEnum`: `from()` (throws),
`tryFrom()` (nullable), `isValid()` (bool).

Beyond validation, value objects carry immutable domain behavior — every
operation returns a new instance and never mutates in place:

```php
use MSSTC4PHP\ValueObject\Network\Cidr;
use MSSTC4PHP\ValueObject\Network\IpAddress;

$block = Cidr::from('10.0.0.0/8');
$block->contains(IpAddress::from('10.1.2.3'));   // true
IpAddress::from('10.1.2.3')->isPrivate();         // true

use MSSTC4PHP\ValueObject\Finance\Decimal;        // needs brick/math
Decimal::from('1')->divide('3', 4);               // Decimal('0.3333')
```

Arithmetic on `Decimal`/`Percentage` is delegated to **`brick/math`** (optional);
without it, those types still construct, compare, and serialize, but arithmetic
throws `MissingDependencyException`. Network math (`Cidr`, IP predicates) is
dependency-free.

> **`isPublic()` is not an SSRF gate.** It is a syntactic classification of the
> address value (excludes private, loopback, link-local, multicast, and IANA
> special-purpose ranges). It does not resolve DNS, cannot see DNS-rebinding,
> and may lag the IANA registries. Use a dedicated egress-filtering layer for
> SSRF defense. Likewise, `Cidr::hostCount()` throws for IPv6 blocks of `/64`
> or wider (the count exceeds PHP's integer range).

## Catalogue

| Group | Value objects |
|---|---|
| Network | `Ipv4Address`, `Ipv6Address`, `IpAddress`, `MacAddress`, `Hostname`, `DomainName`, `Port`, `UrlScheme`, `UrlPath`, `Uri`, `Url`, `Cidr`, `HttpMethod` |
| Identity | `EmailAddress`, `Uuid` (validate, version detect, `v4()`/`v7()`/`v7Now()`) |
| i18n / geo | `CountryCode`, `CurrencyCode`, `LanguageCode` (ISO enums), `Locale`, `Latitude`, `Longitude`, `GeoCoordinates` |
| Finance | `Iban`, `Bic`, `CreditCardNumber` (Luhn), `Decimal` (arbitrary precision) |
| Temporal | `TimeOfDay`, `Duration` (ISO 8601), `DateRange` |
| Primitives | `NonEmptyString`, `BoundedString`, `PositiveInt`, `NonNegativeInt`, `IntRange`, `Percentage` |
| Format | `Slug`, `HexColor`, `Base64String`, `JsonString`, `PhoneNumber`, `SemVer`, `ClassString` |

Full money handling is delegated to `brick/money`: pair `CurrencyCode` with
`Brick\Money\Money`, and use the bundled `BrickMoneyType` to persist it with
Doctrine.

`CountryCode`, `CurrencyCode`, and `LanguageCode` are `string`-backed enums
generated from `symfony/intl` and committed, so they carry no runtime
dependency. Regenerate after an intl update:

```bash
php tools/generate-iso-enums.php
```

## Doctrine ORM

Register the bundled DBAL types once at bootstrap (bare Doctrine):

```php
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm\TypeRegistrar;

TypeRegistrar::registerAll(); // idempotent
```

Under Symfony, map them declaratively instead:

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        types:
            vo_email_address: MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm\EmailAddressType
            vo_uuid: MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm\UuidType
            # ... see TypeRegistrar::types() for the full map
```

```php
#[ORM\Column(type: 'vo_email_address')]
private EmailAddress $email;
```

`CountryCode`/`CurrencyCode`/`LanguageCode` are backed enums — map them with
Doctrine's native `enumType`, no custom type needed.

## Doctrine MongoDB ODM

```php
use MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm\TypeRegistrar;

TypeRegistrar::registerAll();
```

```php
#[ODM\Field(type: 'vo_email_address')]
private EmailAddress $email;
```

## Symfony Validator

Validate raw input against a value object's rules without constructing it.
Works inside Symfony and standalone:

```php
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\AssertEmailAddress;
use Symfony\Component\Validator\Validation;

$validator = Validation::createValidator();
$violations = $validator->validate($_POST['email'], new AssertEmailAddress());
```

Or the generic constraint for any string value object:

```php
use MSSTC4PHP\ValueObject\Bridge\Symfony\Validator\ValidValueObject;
use MSSTC4PHP\ValueObject\Network\Ipv4Address;

new ValidValueObject(Ipv4Address::class);
```

## Development

```bash
make check   # phpstan (max) + cs-fixer + rector + deptrac + phpunit
make fix     # apply cs-fixer + rector
```

## License

MIT.
