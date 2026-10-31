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
  `CurrencyCode::displayFractionDigits()` (CLDR formatting digits) and
  `CurrencyCode::isoMinorUnits()` (ISO 4217 minor units, `null` for historic codes —
  use this one for monetary arithmetic; the two disagree for 16 currencies). Native `from()` throws `\ValueError` on unknown
  codes; `fromAlpha3/fromNumeric` throw `InvalidCountryCodeException`.
- `Locale` — BCP 47 `lang[-REGION]`; composes `LanguageCode` + optional
  `CountryCode`; `language()`/`region()`.
- `Latitude` (−90..90), `Longitude` (−180..180), `GeoCoordinates` (composite,
  `toArray()`/`jsonSerialize()` → `{lat, lng}`).

## Finance (`src/Finance/`)
- `Iban` — strips spaces, uppercases, validates format + ISO 7064 mod-97 (via
  `bcmath`); `countryCode()`/`checkDigits()`.
- `Bic` — SWIFT/BIC 8 or 11 chars; `bankCode/countryCode/locationCode/branchCode`.
- `CreditCardNumber` — strips separators, 12–19 digits, Luhn check;
  `last4()`, `brand()` (`visa|mastercard|amex|discover|unknown`).
- `Decimal` — arbitrary-precision numeric string implementing `Comparable`
  (`bccomp` at max scale); normalizes sign/leading/trailing zeros; `isZero()`.

## Temporal (`src/Temporal/`)
- `TimeOfDay` — `HH:MM[:SS]`, normalized to `HH:MM:SS`; `hour/minute/second`.
- `Duration` — ISO 8601 duration, validated via `\DateInterval` + regex;
  `toDateInterval()`.
- `DateRange` — composite of two `\DateTimeImmutable` with `start <= end`;
  `contains()`, `overlaps()`, `jsonSerialize()` → `{start, end}` (ATOM).

## Network extras (`src/Network/`)
- `Cidr` — `<ip>/<prefix>` (v4 0–32, v6 0–128); `address(): IpAddress`,
  `prefixLength()`, `version()`.
- `HttpMethod` — backed enum (GET…CONNECT) with `isSafe()`/`isIdempotent()`.

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

## v1.1 behavior enrichment (2026-07-20)

Value objects gained domain operations (additive, semver-minor). Highlights:

- **Comparison ergonomics** — `Support/ComparableHelpers` trait adds
  `isLessThan/isGreaterThan/isLessThanOrEqual/isGreaterThanOrEqual/min/max`
  to every `Comparable` (int/float bases, `Decimal`, `SemVer`, `TimeOfDay`).
  `TimeOfDay` now implements `Comparable` (string compare == chronological).
- **`Cidr`** — `contains(IpAddress)`, `firstAddress/lastAddress/netmask`,
  `broadcast()` (`?IpAddress`, null for v6), `hostCount()` (throws
  `\RangeException` for wide IPv6), `overlaps/isSubnetOf`. All zero-dep via
  `inet_pton` binary masking (v4 + v6).
- **`Ipv4Address`/`Ipv6Address`/`IpAddress`** — `isPrivate/isLoopback/
  isLinkLocal/isMulticast/isPublic` (RFC ranges via `Support/ClassifiesIpAddress`
  trait) and `isInRange(Cidr)`. `IpAddress` delegates to the concrete family.
- **`Url`/`Uri`** — `Support/UriComponents` trait: `with{Scheme,Host,Port,Path,
  Query,Fragment}()` (revalidate via ctor) + `queryParameters(): array`.
- **`EmailAddress::domainName(): ?DomainName`** (nullable — a valid email domain
  may be non-registrable). `DomainName`/`Hostname` gained `labels()/tld()/
  isSubdomainOf()` via `Support/DottedLabels` (label-boundary, not string-suffix).
- **`Duration`** — `plus/minus/multipliedBy` (three calendar-safe buckets:
  months, days, seconds), `isZero`, `toSeconds(?anchor)`, `fromSeconds()`.
  NO `Comparable` (ISO durations aren't totally ordered without an anchor).
- **`DateRange`** — `duration(): Duration`, `intersect/gap` (`?self`),
  `contains(self)` (via union param), `isAdjacentTo`.
- **`TimeOfDay`** — `plus/minus(Duration)` time-only, wraps mod 24h, rejects
  date-bearing durations.
- **`IntRange`** — `length/overlaps/intersect`. **`SemVer`** — `isStable`,
  `nextMajor/nextMinor/nextPatch` (drop prerelease/build).
- **`Decimal`/`Percentage` arithmetic** — delegated to optional `brick/math`
  (guarded by `class_exists`, throws `MissingDependencyException` when absent).
  `Decimal`: `add/subtract/multiply/divide/round/abs/negated/sign` (default
  rounding `RoundingMode::HalfUp`). `Percentage`: `applyTo/of/fromRatio`.
