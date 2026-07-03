# Domain model — value object catalogue

All value objects are immutable and validate at construction. `from()` throws,
`tryFrom()` returns null, `isValid()` returns bool.

## Network (`src/Network/`)
- `Ipv4Address`, `Ipv6Address` — `filter_var` with the matching flag.
- `IpAddress` — v4 OR v6; `version(): int` (4|6); `fromString()` alias.
- `MacAddress` — accepts colon/hyphen/dotted notation; **normalizes to lowercase
  colon form**, so equal MACs written differently compare equal.
- `Hostname` — RFC 1123 labels, ≤253, lowercased. `DomainName` — hostname with
  ≥2 labels and an alphabetic TLD.
- `Port` — `int<0, 65535>`.
- `UrlScheme` (lowercased), `UrlPath` (RFC 3986 path, empty allowed).
- `Uri` — requires a scheme; accessors `scheme/host/port/path/query/fragment`.
- `Url` — http/https + host; `host(): Hostname`.

## Identity (`src/Identity/`)
- `EmailAddress` — `FILTER_VALIDATE_EMAIL`, no DNS; `local()`/`domain()` split on
  the last `@`.
- `Uuid` — canonical 8-4-4-4-12, stored lowercased. `version()`/`variant()`.
  `v4()` and `v7(int $unixMillis)` generate via `random_bytes` — **the timestamp
  is a parameter; the VO never reads the wall clock** (keeps it pure/testable).

## i18n / geo (`src/I18n/`, `src/Geo/`)
- `CountryCode` (ISO 3166-1 alpha-2), `CurrencyCode` (ISO 4217),
  `LanguageCode` (ISO 639-1) — **backed enums generated from `symfony/intl`**.
  `CountryCode::alpha3()/numeric()/fromAlpha3()/fromNumeric()`,
  `CurrencyCode::minorUnits()`. Native `from()` throws `\ValueError` on unknown
  codes; `fromAlpha3/fromNumeric` throw `InvalidCountryCodeException`.
- `Locale` — BCP 47 `lang[-REGION]`; composes `LanguageCode` + optional
  `CountryCode`; `language()`/`region()`.
- `Latitude` (−90..90), `Longitude` (−180..180), `GeoCoordinates` (composite,
  `toArray()`/`jsonSerialize()` → `{lat, lng}`).

## Primitives (`src/Primitive/`)
- `NonEmptyString`, `PositiveInt`, `NonNegativeInt`, `Percentage` (0..100).
- `BoundedString` — per-instance min/max length (`mb_strlen`); equality includes
  the bounds.
- `IntRange` — `[min, max]` with `contains()`.

## Format (`src/Format/`)
- `Slug`, `HexColor` (lowercased), `Base64String` (`decoded()`), `JsonString`
  (validation only), `PhoneNumber` (E.164).
- `SemVer` — implements `Comparable`; semver.org precedence (prerelease < release);
  `major/minor/patch/prerelease/build`.
- `ClassString` — an existing class/interface/trait/enum FQCN, typed
  `class-string`; `isInstanceOf()`/`isSubclassOf()`.
