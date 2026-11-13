# Changelog

## [Unreleased]

To be released as 1.1.1.

**Fixed:** `Payload\ValueObjectMapper` no longer supports an interface or an abstract class without a codec.
`supports()`, `supportsUntrusted()` and `isScalarBacked()` return false for them, and `fromPayload()`,
`fromUntrustedPayload()` and `payloadType()` throw the `LogicException` of an unsupported class. Before, an interface
such as one extending `StringConvertible` and `ValueObject` was reported as supported and reading it failed with an
`\Error` ("Cannot call abstract method"), a server error for request input.

## 1.1.0 - 2026-11-11

**Added:** the storage form every framework bridge shares.

- `Payload\ValueObjectMapper`: value object or ISO enum to a string, number or flat array, and back. Numeric strings
  are read as numbers, the way PDO returns them.
- `Payload\PayloadCodec` and the codecs `BoundedStringCodec`, `IntRangeCodec`, `GeoCoordinatesCodec`,
  `DateRangeCodec`, moved from `msstc4php/vo-base-doctrine` (where they were `@internal`) with the same stored
  formats.
- `Exception\InvalidPayloadException` for a payload of the wrong type or shape.
- A trust boundary: `fromUntrustedPayload()` and `supportsUntrusted()` refuse classes whose codec implements
  `Payload\TrustedSourceOnly` (`BoundedStringCodec`: its payload carries the length limits). `isScalarBacked()` tells
  whether a class fits a single string or number, `isComposite()` whether it is stored as an array, `payloadType()`
  its JSON type (for error messages that must not name internal classes). Refusing a trusted-source-only class is an
  `InvalidPayloadException` (client input); only an unsupported class is a `LogicException`.
- Strict reading: canonical integer strings, plain decimal strings, and `DateRange` boundaries only as ISO 8601
  instants with an offset (the Doctrine codec accepted anything `DateTimeImmutable` parses, such as `"now"`).

## 1.0.0 - 2026-11-11

First stable release: from here on a breaking change needs 2.0.

**Changed (BC):** the `UriComponents` trait moved from `MSSTC4PHP\ValueObject\Support` to
`MSSTC4PHP\ValueObject\Network`. Only code that used the trait directly is affected; `Url` and `Uri` are unchanged.

- `Testing\RandomValueObjects` is no longer experimental. The generator set and return types follow semver; the
  values a seed draws may change in any release.

**Upgrading:** replace `use MSSTC4PHP\ValueObject\Support\UriComponents` with
`use MSSTC4PHP\ValueObject\Network\UriComponents`.

## 0.4.1 - 2026-11-09

- Package metadata and docs point at the renamed bridges: `msstc4php/vo-base-doctrine` (was
  `msstc4php/vo-base-doctrine-bridge`) and `msstc4php/vo-base-bundle` (was `msstc4symfony/vo-base-bridge`). No code
  changes.

## 0.4.0 - 2026-11-07

**Changed (BC):** validation is stricter. Stored values that used to pass may now fail on hydration; find them with
`tryFrom()` and clean them before upgrading.

- Every pattern anchored with `$` now uses the `D` modifier, so a trailing newline is rejected (`Slug`, `Uuid`, `Iban`,
  `Bic`, `HexColor`, `SemVer`, `TimeOfDay`, `Duration`, `UrlScheme`, `MacAddress`, `Hostname`,
  `DomainName`, `PhoneNumber`, `Decimal`, `CreditCardNumber`).
- `Url` and `Uri` reject spaces, control characters and DEL, which `parse_url()` let through: a value such as
  `"https://a.example/\r\nSet-Cookie: x"` was accepted. Loose values like `data:text/plain,hello world` must now
  percent-encode the space.
- Exception messages escape control characters and cut input longer than 200 bytes at a UTF-8 boundary
  (`InvalidValueObjectException::excerpt()`), so a rejected value cannot inject lines into logs. Card numbers are
  reduced to their length and last four characters, IBANs to country and last four.
- `UrlPath` validates by scanning for a forbidden character, so long paths no longer trip PCRE limits.

**Added:**

- `QueryString` and `QueryParameter`: a query component that keeps pair order and duplicates and never rewrites
  names the way `parse_str()` does (`a.b`, `a[]`); `get()`, `getAll()`, `has()`, `names()`, `with()`, `withAdded()`,
  `without()`, `fromParameters()`. RFC 3986 characters plus raw `[`/`]`; edits keep untouched segments verbatim.
- `Uri::queryString()` / `Url::queryString()` and `withQueryString()`. `queryString()` percent-encodes what a
  `QueryString` cannot hold (non-ASCII, `{`, a stray `%`), so it never fails on a valid URI.
- `Testing\RandomValueObjects` (experimental until 1.0): seeded, dependency-free generators of valid value objects
  for consumers' tests. Seeded sequences are stable within one library version.

## 0.3.0 - 2026-11-07

- `IpRange`: an inclusive first-last address range with containment, overlap, `intersect()`, `isAdjacentTo()`,
  exact `addressCount()` and a minimal CIDR cover via `toCidrs()`; `Cidr::toRange()` converts a block.
- `Cidr::firstAddress()`, `lastAddress()` and `netmask()` render RFC 5952 text on every platform. On glibc,
  addresses in `::/96` used to come out as `::1.2.3.4`; they are now `::102:304`, as on musl and macOS.
- `Cidr::addressCount()`: the exact address count as a decimal string, also for IPv6 blocks where
  `hostCount()` overflows.

## 0.2.0 - 2026-11-07

**Breaking:** `CurrencyCode::isoMinorUnits()` returns a value instead of `null` for 47 codes, so code that used
`null` to reject withdrawn currencies starts accepting them.

- `brick/math` and `brick/money` 0.15 up to `brick/math` 1.0 are supported; both are declared as conflicts below 0.15
  so consumers cannot hold an ISO table older than the enum's.
- ISO 4217 data comes from `brick/money` 0.15.2. Withdrawn codes (ADP, BEF, ATS, ...) and the current `XCG` now
  report their ISO minor units instead of `null`; `null` remains where the ISO data has no value (`CNH`, `XEU`, ...).
- `CurrencyCode::XAD` is added; CLDR does not know it yet, so its display digits fall back to ISO minor units.

## 0.1.0 - 2026-11-07

- Immutable value objects with no framework dependencies. Doctrine and Symfony integration moved to
  `msstc4php/vo-base-doctrine-bridge` and `msstc4symfony/vo-base-bridge`.
- `brick/math` support starts at 0.14.2 (declared as a conflict below it): `Decimal` and `Percentage`
  arithmetic relies on PascalCase `RoundingMode` cases. The earlier `^0.12 || ^0.13` range never worked.
- Currency codes and CLDR display digits come from `symfony/intl` 7.4.22. Against the unreleased snapshot this
  drops `CurrencyCode::CNX` and moves `PKR` display digits from 0 to 2; ISO minor units (from `brick/money`) are
  unchanged.
