# Known issues, quirks, and deliberate scope cuts

## Framework quirks

- **DBAL 4 migration diff-noise.** DBAL 4 removed the `(DC2Type:…)` column-comment
  mechanism, so a migrations tool can no longer distinguish custom-typed
  columns that share a SQL declaration — it may emit repeated no-op migrations
  for VO columns. Cannot be fixed in-library; give a VO column a distinctive
  declaration where it matters, or ignore the diff noise. (dbal #6257/#7327,
  migrations #1435/#1441.)
- **PHP 8.4:** a `readonly` property cannot declare property hooks, and a purely
  virtual hooked property cannot be `readonly` — choose per property. Implicitly
  nullable params (`Foo $x = null`) are deprecated; always write `?Foo $x = null`.
- **ODM `Type::registerType` is unconditional** (overwrites), unlike DBAL's
  `addType`. The ODM `TypeRegistrar` guards with `Type::hasType` for idempotency.

## Quirks in this codebase

- **Reserved words as enum case names.** Generated `CountryCode`/`LanguageCode`
  cases include `DO`, `AS`, `OR` (Dominican Republic, American Samoa, Odia).
  PHP 8.4 accepts reserved keywords as enum case names, so this is fine.
- **`GeoCoordinates` persistence** is a single JSON column (ORM) / embedded array
  (ODM), **not** a mapped embeddable. An embeddable would need ORM metadata
  on the class, which would couple the core to Doctrine and break the
  autoload-safety rule. Map it as an embeddable in the consuming app if
  sub-field querying is needed. (Deviation from the plan's R10.)

## Adding more coverage

- **A new string VO field type** is one thin subclass of the bridge's `AbstractStringVoType`
  (ORM) / the ODM base, plus a line in the respective `TypeRegistrar`. Large
  text VOs extend `AbstractTextVoType` (ORM, CLOB) instead. Composite VOs mirror
  `GeoCoordinatesType`/`DateRangeType` (JSON column / BSON array).
- **A dedicated `Assert*` constraint** (in the Symfony bridge) is a ctor-arg-free `final class extends
  ValidValueObject`. Any VO also works out of the box via the generic
  `ValidValueObject(<VoClass>::class)`.

## Still deferred

- **Violation-collecting validation path** — intentionally NOT built; it
  contradicts the fail-fast decision (KTD5). Would be a separate design track,
  not a drop-in.
- A standalone `IpRange` VO (start–end) — `Cidr` covers the common case.
- `brick/money` bridge stores `"CUR amount"` as a single column; a two-column
  (minor-units + currency) mapping could be added if sub-field querying is needed.

## v1.1 behavior semantics (deliberate)

- **`Duration` has no `Comparable`** and `toSeconds()` requires an anchor
  `\DateTimeImmutable` when year/month components are present. ISO 8601
  durations are not totally ordered and not convertible to a fixed second count
  without a calendar anchor (`P1M` vs `P30D` is undefined). Arithmetic uses
  three calendar-safe buckets (months w/ years folding at 12, days, seconds w/
  hours+minutes folding at 60); `minus` throws on any negative bucket (no
  cross-bucket borrow — you cannot subtract 30s from "1 month").
- **`Decimal`/`Percentage` arithmetic needs `brick/math`** (optional dep, also
  pulled in transitively by `brick/money`). Methods guard with
  `class_exists(BigDecimal::class)` and throw `MissingDependencyException`
  (a `\RuntimeException`, NOT `InvalidValueObjectException`). Construction,
  `compareTo`, `isZero`, serialization work without it. The `core-only`
  GitLab CI leg (`composer install --no-dev`) asserts the guard throws.
  `RoundingMode` params are `?RoundingMode = null` (resolved to `HalfUp`
  inside, after the guard) so the default value never triggers class loading
  when `brick/math` is absent.
- **`Cidr::hostCount()` throws `\RangeException`** for IPv6 blocks wider than a
  /65 (host count exceeds PHP int). `broadcast()` returns null for IPv6.
- **`EmailAddress::domainName()` is nullable** — a syntactically valid email
  (e.g. `user@example.c`, 1-char TLD) can have a domain that is not a
  registrable `DomainName`.

## v1.1.x quality hardening (2026-07-20)

- **`isPublic()` excludes IANA special-purpose ranges** (not just RFC1918/
  loopback/link-local/multicast): v4 `0.0.0.0/8`, `100.64.0.0/10` (CGNAT),
  `192.0.0.0/24`, `192.0.2.0/24`, `198.18.0.0/15`, `198.51.100.0/24`,
  `203.0.113.0/24`, `240.0.0.0/4` (covers `255.255.255.255`); v6 `::/128`,
  `::ffff:0:0/96`, `64:ff9b::/96`, `2001:db8::/32`. Exposed as the public
  `isReserved()` predicate. **`isPublic()` is a syntactic classifier, NOT an
  SSRF gate** — documented on the method. A behavior change: addresses in these
  ranges now report `isPublic() === false`.
- **`IntRange::length()` throws `\RangeException` on overflow** (extreme bounds
  like `[PHP_INT_MIN, PHP_INT_MAX]` or any span reaching `PHP_INT_MAX`), rather
  than PHP promoting the subtraction to a float / clamping to 1. The guard is
  written as overflow-safe comparisons because PHPStan does not model int
  overflow (`int - int` is always `int` to it, so an `is_int()`/`is_float()`
  check reads as "always true/false").
- **`ClassifiesIpAddress` trait now lives in `Network`** (was `Support`) — it
  references `Cidr`/`IpAddress`, so keeping it in `Network` avoids a
  Support→Network upward dependency (CR-009). Pure relocation.
- **Mutation floor in CI.** `infection.json5` sets `minMsi: 70` /
  `minCoveredMsi: 84` (current ~72% / ~86%, buffered for run-variance); the
  GitLab `infection` job (installs pcov) enforces it. **Equivalent-mutant
  policy:** genuine equivalents are ignored narrowly per-mutator in
  `infection.json5` with a reason (currently `GreaterThan`/`LessThan`/
  `DecrementInteger` on `IntRange::length` — the `=== PHP_INT_MAX` fallback and
  `max(1, …)` clamp make them unkillable). Do NOT chase 100% MSI; the many
  remaining escaped mutants are equivalent (e.g. `(int) substr($v,0,2)` on a
  canonical `HH:MM:SS`, unreachable `inet_pton(...) === false` guards).

## `composer update` refuses to resolve on PHP older than 8.4.27

`roave/security-advisories` `dev-latest` conflicts with PHP `>=8.4,<8.4.27`, so a
full resolve fails on such a runtime. A hash-only refresh still works and changes
nothing else: `composer update --lock --ignore-platform-req=php`.

## `symfony/intl` is pinned to an exact version in `composer-ci.json`

Pinned at 7.4.22. Moving from 7.4.17 dropped `CNX` (a public enum case) and changed `PKR` display digits from
0 to 2; 8.1.10 generates the same data. Any bump can do the same, and
`IsoEnumsUpToDateTest` fails on any such change. Bump the pin only together with
`php tools/generate-iso-enums.php` and a look at `iso-minor-units.snapshot.json`.

## Generated enums now go through the style tools

The shared templates cannot exclude paths, so the generator's output must stay clean for PHP-CS-Fixer and Rector.

## `beStrictAboutCoverageMetadata` fails only under coverage

`make test` can be green while `make infection` reports 0 mutants: the initial run hits a risky test. A test touching
another class needs `#[UsesClass]`. Check with `XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text=/dev/null`.

## `brick/math` floor is 0.14.2, not 0.12

`Decimal` uses the PascalCase `RoundingMode::HalfUp`, which first exists in brick/math 0.14.2 (0.14.0/0.14.1 only
have `HALF_UP`). The old `^0.12 || ^0.13 || ^0.14` range was never true and surfaced only in the `lowest` CI job.
brick/math is suggest-only, so `composer.json` carries `conflict: brick/math <0.14.2` to protect consumers. The
upper bound (0.14) lags upstream: brick/math is at 1.0 and brick/money at 0.15.
