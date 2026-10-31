# msstc4php/vo-base

Immutable, self-validating value objects for **PHP 8.4+** with **zero runtime
dependencies**. Framework integration lives in separate packages, so this one
stays a plain library.

## Install

```bash
composer require msstc4php/vo-base
```

Optional, for arithmetic on `Decimal` and `Percentage`:

```bash
composer require brick/math
```

Without it those methods throw `MissingDependencyException`; construction and
comparison still work.

## Design

Every value object is a `final readonly` class that validates in its
constructor and throws a typed exception on invalid input — there is no
partially-valid state. Validation lives in **one place** per type, so anything built on top — a
Doctrine type, a form constraint — reuses it rather than restating it.

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
`Brick\Money\Money`. `CurrencyCode::isoMinorUnits()` is the value monetary
arithmetic needs; `displayFractionDigits()` is CLDR formatting and the two
disagree for 16 currencies.

`CountryCode`, `CurrencyCode`, and `LanguageCode` are `string`-backed enums
generated at build time and committed, so they carry no runtime dependency.
Regenerate after a data update:

```bash
php tools/generate-iso-enums.php
```

## Framework integration

The core carries no framework dependency, and `deptrac` fails the build if one
appears. Integration lives in two separate packages:

| Package | What it gives you |
|---|---|
| [`msstc4php/vo-base-doctrine-bridge`](../vo-base-doctrine-bridge) | 37 Doctrine field types per branch (ORM and MongoDB ODM), plus money embeddables that aggregate in SQL |
| [`msstc4symfony/vo-base-bridge`](../../msstc4symfony/vo-base-bridge-symfony) | Symfony Validator constraints and a bundle that registers both them and the Doctrine types from one line in `bundles.php` |

Install whichever you need; neither is required by this package, and each pulls
in only the stack it actually bridges.

## Development

```bash
make check   # phpstan (max) + cs-fixer + rector + deptrac + phpunit
make fix     # apply cs-fixer + rector
```

## License

MIT.
