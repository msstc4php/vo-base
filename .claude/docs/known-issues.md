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

## Deliberately not built

- **Violation-collecting validation path** — it contradicts the fail-fast
  decision (KTD5). Declined again on 2026-11-10 UTC; would be a separate design
  track, not a drop-in.

Queryable money is no longer deferred: `vo-base-doctrine` ships the two-column
`MoneyAmount` (minor units + currency) and `PreciseMoneyAmount` embeddables.

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
  CI job (`composer update --no-dev`) only proves the core loads and constructs
  without dev dependencies; the guard itself is covered by unit tests.
  `RoundingMode` params are `?RoundingMode = null` (resolved to `HalfUp`
  inside, after the guard) so the default value never triggers class loading
  when `brick/math` is absent.
- **`Cidr::hostCount()` throws `\RangeException`** for IPv6 blocks of /65 or
  wider (63+ host bits exceed PHP int); `Cidr::addressCount()` gives the exact
  count as a decimal string. `broadcast()` returns null for IPv6.
- **`IpRange` stores RFC 5952 bounds, `IpAddress`/`Cidr` keep the input
  spelling**, so `IpRange::from('2001:DB8::1-…')->firstAddress()` does not
  `equals()` `IpAddress::from('2001:DB8::1')`; use `contains()`. The formatter is
  `PackedAddress::unpack()`, not `inet_ntop()`, whose `::/96` output differs
  between glibc (`::1.2.3.4`) and musl/macOS (`::102:304`).
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
- **Mutation floor in CI.** `infection.json5` is the byte-identical standard
  template, so the floor lives in `checks.yml`: the `infection` job (GitHub
  Actions, xdebug coverage, `main` pushes only) passes `--min-msi=79
  --min-covered-msi=79`. **Equivalent-mutant
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

## `brick/math` and `brick/money` floor is 0.15

`Decimal` uses the PascalCase `RoundingMode::HalfUp`, which first exists in brick/math 0.14.2 (0.14.0/0.14.1 only
have `HALF_UP`). The old `^0.12 || ^0.13 || ^0.14` range was never true and surfaced only in the `lowest` CI job.
brick/math and brick/money are suggest-only, so `composer.json` carries `conflict: brick/math <0.15` and
`brick/money <0.15`. The floor is 0.15 rather than 0.14.2 because the enum's ISO data comes from brick/money 0.15,
which needs math >= 0.15: a consumer on brick/money 0.10-0.14 would hold a second, older ISO table (no withdrawn
codes, no XCG) that disagrees with `CurrencyCode::isoMinorUnits()`. The range runs up to `^1.0`.

## `brick/money` is pinned to an exact version in `composer-ci.json`

It is the generator's ISO 4217 source, so a different version regenerates `CurrencyCode` and fails
`IsoEnumsUpToDateTest`. 0.15 added the ISO minor units of withdrawn codes: 47 cases moved from `null` to a value,
46 withdrawn codes plus the current `XCG` (introduced 2025). It also added `XAD`, which CLDR does not know yet, so
the generator takes the union of both code sets and falls back to ISO minor units for display digits. Bump it like
`symfony/intl`: regenerate, then review the snapshot diff.

## Intended BC breaks turn the `bc-check` job red until the release tag exists

`bc-check` compares HEAD with the nearest stable tag, and Roave treats even an added enum case as a break (an
exhaustive `match` in a consumer stops compiling). On 0.x such a change ships as a minor release: the commit before
the tag is red on that one job (`c6010f7`: `CurrencyCode::XAD was added`), and tagging it moves the baseline. Push
nothing else between the red commit and its tag.

## PHP 8.5 deprecates `chr()` outside 0..255

PHPUnit runs with `failOnDeprecation`, so the PHP 8.5 job fails on `chr(-1)` even though PHP 8.4 silently wraps it.
Mask byte values explicitly (`chr($value & 0xFF)`); there is no local 8.5 runtime, only CI catches this.

## `QueryString` decodes `+` as a space

Decoding follows application/x-www-form-urlencoded, so a literal `+` in a non-form query (`mailto:?to=a+b@x`)
comes back as a space from `get()`; the raw segment itself is never rewritten by `with*()`. There is deliberately no
Doctrine type or Symfony constraint for `QueryString`: queries are rarely stored on their own.

## Validate by scanning for a forbidden character, anchor with `\z` or `D`

A whole-value pattern like `^(?:[...]|%XX)*$` returns `false` once PCRE's JIT stack (`pcre.jit=1` is the default)
or recursion limit is hit on long input, and `$` without `D` accepts a trailing newline. `QueryString` and `UrlPath`
scan for one forbidden character instead (`testLongQueriesStayClearOfPcreLimits`); every other `$`-anchored pattern
uses `D`. `ControlCharacterRejectionTest` lists each string type explicitly and checks control characters as prefix,
suffix and infix.

## Exception messages go through `excerpt()`

Messages that quote the rejected input use `InvalidXException::excerpt($value)` (inherited from
`InvalidValueObjectException`): control characters escaped, long input cut at a UTF-8 boundary. Numeric types pass
their number directly.

`excerpt()` also escapes backslashes (a literal `\n` stays distinguishable from a newline), renders only bytes that are
not valid UTF-8 as `\xNN`, and steps back at most three bytes to cut on a character boundary. Two types never quote
their input: `CreditCardNumber` reports only the length (a failed number is often a real PAN, and raw input may end
in a CVV that still looks like digits; PCI DSS), `Iban` keeps country and last four of IBAN-shaped input (15-34 chars,
`CC99...`) and only the length for anything else (personal data; may be any value typed into the wrong field).

`QueryString::fromLoose()` is public only so `UriComponents` can reach it; it is `@internal`, not API.

## Pending for the next breaking release

- Move the `UriComponents` trait from `Support/` to `Network/`; it is network-specific and closes the
  `Support` <-> `Network` cycle, but renaming it breaks anyone who used the trait directly.
