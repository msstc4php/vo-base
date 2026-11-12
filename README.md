# msstc4php/vo-base

Immutable, self-validating value objects for **PHP 8.4+** with **zero runtime
dependencies**. Framework integration lives in separate packages, so this one
stays a plain library.

## Install

Not on Packagist yet, so register the GitHub repository first:

```bash
composer config repositories.vo-base vcs https://github.com/msstc4php/vo-base
composer require msstc4php/vo-base:^1.0
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

use MSSTC4PHP\ValueObject\Network\IpRange;
IpRange::from('10.0.0.5-10.0.0.20')->toCidrs();   // 10.0.0.5/32, 10.0.0.6/31, 10.0.0.8/29, ...

use MSSTC4PHP\ValueObject\Finance\Decimal;        // needs brick/math
Decimal::from('1')->divide('3', 4);               // Decimal('0.3333')
```

Arithmetic on `Decimal`/`Percentage` is delegated to **`brick/math`** (optional);
without it, those types still construct, compare, and serialize, but arithmetic
throws `MissingDependencyException`. Network math (`Cidr`, `IpRange`, IP predicates) is
dependency-free.

> **`isPublic()` is not an SSRF gate.** It is a syntactic classification of the
> address value (excludes private, loopback, link-local, multicast, and IANA
> special-purpose ranges). It does not resolve DNS, cannot see DNS-rebinding,
> and may lag the IANA registries. Use a dedicated egress-filtering layer for
> SSRF defense. Likewise, `Cidr::hostCount()` throws for IPv6 blocks of `/65`
> or wider (the count exceeds PHP's integer range); `Cidr::addressCount()` and
> `IpRange::addressCount()` return the exact count as a decimal string.

## Test data

`Testing\RandomValueObjects` generates valid instances for your own tests and property-style checks; pass a seed
to make a run reproducible within one release (a new release may draw different values for the same seed):

```php
use MSSTC4PHP\ValueObject\Testing\RandomValueObjects;

$random = new RandomValueObjects(seed: 42);
$random->ipRange();        // e.g. 41.12.7.200-198.51.3.9
$random->iban();           // a DE IBAN with a valid checksum
$random->currencyCode();   // always one with ISO minor units
```

## Catalogue

| Group | Value objects |
|---|---|
| Network | `Ipv4Address`, `Ipv6Address`, `IpAddress`, `MacAddress`, `Hostname`, `DomainName`, `Port`, `UrlScheme`, `UrlPath`, `Uri`, `Url`, `QueryString`, `QueryParameter`, `Cidr`, `IpRange`, `HttpMethod` |
| Identity | `EmailAddress`, `Uuid` (validate, version detect, `v4()`/`v7()`/`v7Now()`) |
| i18n / geo | `CountryCode`, `CurrencyCode`, `LanguageCode` (ISO enums), `Locale`, `Latitude`, `Longitude`, `GeoCoordinates` |
| Finance | `Iban`, `Bic`, `CreditCardNumber` (Luhn), `Decimal` (arbitrary precision) |
| Temporal | `TimeOfDay`, `Duration` (ISO 8601), `DateRange` |
| Primitives | `NonEmptyString`, `BoundedString`, `PositiveInt`, `NonNegativeInt`, `IntRange`, `Percentage` |
| Format | `Slug`, `HexColor`, `Base64String`, `JsonString`, `PhoneNumber`, `SemVer`, `ClassString` |

Full money handling is delegated to `brick/money`: pair `CurrencyCode` with
`Brick\Money\Money`. `CurrencyCode::isoMinorUnits()` is the value monetary
arithmetic needs; `displayFractionDigits()` is CLDR formatting and the two
disagree for 25 codes (15 of them in current use).

`CountryCode`, `CurrencyCode`, and `LanguageCode` are `string`-backed enums
generated at build time and committed, so they carry no runtime dependency.
Regenerate after a data update:

```bash
php tools/generate-iso-enums.php
```

## Storage form

`Payload\ValueObjectMapper` turns a value object into what a column or a wire format holds, and back. Framework
bridges are built on it, and it is the place to start when you write one of your own:

```php
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;

$mapper = new ValueObjectMapper();
$mapper->toPayload(EmailAddress::from('a@example.com'));   // 'a@example.com'
$mapper->toPayload(new IntRange(1, 5));                     // ['min' => 1, 'max' => 5]
$mapper->fromPayload(Port::class, '8080');                  // Port 8080: numeric strings from PDO are accepted
```

String-backed value objects map to strings, int- and float-backed ones to numbers, the ISO enums to their codes,
and the composites (`BoundedString`, `IntRange`, `GeoCoordinates`, `DateRange`) to flat arrays through a
`Payload\PayloadCodec`; pass your own codecs to the constructor for your own composites. A wrong type or shape
throws `InvalidPayloadException`, invalid content the value object's own exception; both extend
`InvalidValueObjectException`.

Use `fromPayload()` for storage your application wrote and `fromUntrustedPayload()` for request input. The latter
refuses classes whose codec implements `Payload\TrustedSourceOnly`: a `BoundedString` payload carries its own length
limits, so a client could send its own `maxLength`. `supportsUntrusted()` tells a bridge up front.

Payloads are read strictly: integers as canonical digit strings (`"080"` is rejected), floats as plain decimals,
`DateRange` boundaries only as ISO 8601 instants with an offset.

## Framework integration

The core carries no framework dependency, and `deptrac` fails the build if one
appears. Integration lives in separate packages, all built on the same storage form
(`Payload\ValueObjectMapper`), so a value written by one reads back through another:

| Package | What it gives you |
|---|---|
| [`msstc4php/vo-base-doctrine`](https://github.com/msstc4php/vo-base-doctrine) | 38 Doctrine field types per branch (ORM and MongoDB ODM), plus money embeddables that aggregate in SQL |
| [`msstc4php/vo-base-bundle`](https://github.com/msstc4php/vo-base-bundle) | Symfony: Validator constraints, Serializer normalizer, route argument resolver, and the Doctrine types from one line in `bundles.php` |
| [`msstc4php/vo-base-laravel`](https://github.com/msstc4php/vo-base-laravel) | Laravel 13.30+: Eloquent cast, validation rule, `$request->valueObject()`, form-request helper, route parameters |
| [`msstc4php/vo-base-yii2`](https://github.com/msstc4php/vo-base-yii2) | Yii 2: validator, ActiveRecord behavior, model values, action parameters |
| [`msstc4php/vo-base-codeigniter4`](https://github.com/msstc4php/vo-base-codeigniter4) | CodeIgniter 4: Entity and Model casts, validation rule, validated data, controller arguments |
| [`msstc4php/vo-base-cycle`](https://github.com/msstc4php/vo-base-cycle) | Cycle ORM 2: typecast handler |
| [`msstc4php/vo-base-spiral`](https://github.com/msstc4php/vo-base-spiral) | Spiral 3: filter caster, route argument interceptor, `vo:valid` checker (storage via `vo-base-cycle`) |

Install whichever you need; none is required by this package, and each pulls
in only the stack it actually bridges.

## Development

```bash
make check   # php -l, PHPStan level max, cs-fixer, composer validate/audit, Rector, deptrac
make test    # PHPUnit
make fix     # apply cs-fixer + rector
```

## License

MIT.
