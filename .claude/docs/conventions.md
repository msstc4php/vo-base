# Conventions (project-specific)

- **Construction convention:** every VO exposes `from()` (throws),
  `tryFrom()` (nullable), `isValid()` (bool), mirroring `BackedEnum`. Scalar
  bases provide these; bespoke VOs replicate them.
- **`isValid()`/`tryFrom()` are typed, not `mixed`.** The plan suggested
  `mixed` signatures; we use the concrete scalar type (`string`/`int`) to keep
  PHPStan max clean. The bridge's validator does the `is_string` guard before
  delegating. (Deviation from the plan, on purpose.)
- **`StringConvertible::from()` must return `: static`, not `: self`.** An
  interface method returning `static` cannot be satisfied by a `: self`
  implementation (self is wider). `Uuid` and `ClassString` were changed from
  `: self` to `: static` when they adopted the interface.
- **Per-VO exceptions** all extend `InvalidValueObjectException`
  (itself `extends \InvalidArgumentException`). Catch one kind or the whole family.
- **Generated ISO enums are excluded from cs-fixer and Rector** (cs-fixer
  `notPath`, rector `withSkip`) — the generator owns their formatting.
  `IsoEnumsUpToDateTest` fails if the committed files drift from generator output.
- **Bridge converter params stay `mixed`** (framework-imposed) but are narrowed
  with `is_string`/`is_numeric`/`instanceof` guards before any cast, so
  PHPStan max passes without `@phpstan-ignore`.
- **`#[\Override]` on all overridden methods** — Rector adds it; keep it when
  writing new bridge types.

## Tooling (byte-identical to the php84 templates of msstc4symfony/bundle-standard)

- Configs are shared: `.php-cs-fixer.dist.php`, `rector.php`, `phpstan*.neon` (level max), `phpunit.xml.dist`,
  `infection.json5`, `Makefile`, `.gitignore`. CI job `templates` compares them; do not edit them here.
- Install with `COMPOSER=composer-ci.json`; `composer.json` alone is the "no optional libraries" profile, where
  tests needing brick or symfony/intl skip through `#[RequiresMethod]`.
- **`beStrictAboutCoverageMetadata` is ON.** Tests constructing collaborator VOs list them with `#[UsesClass]`.
- **CI:** `.github/workflows/checks.yml` only. `make check` = php -l + PHPStan + PHP-CS-Fixer + composer
  validate/audit + Rector + deptrac; `make test` runs PHPUnit.

## Behavior traits (`src/Support/`, added v1.1)

Cross-cutting VO behavior lives in small traits mixed into the concrete types,
keeping duplication out while respecting that VOs don't share a base beyond the
scalar abstractions:

- **`ComparableHelpers`** — `isLessThan`/`.../min`/`max` on any `Comparable`.
  Uses `@param static $other` so `min`/`max` return `static` type-cleanly while
  the native param stays `Comparable` (runtime safety via `compareTo`'s guard).
- **`UriComponents`** — `with*` mutators + `queryParameters()` for `Url`/`Uri`.
  Every mutator rebuilds the string and passes it back through the concrete
  ctor, so invalid combinations throw the type's own exception.
- **`DottedLabels`** — `labels()/tld()/isSubdomainOf()` for `Hostname`/
  `DomainName` (label-boundary comparison, not `str_ends_with`).
- **`ClassifiesIpAddress`** — shared `isPublic()` (negation of the family
  predicates) + `isInRange()`/`inRange()` for `Ipv4Address`/`Ipv6Address`.

Convention: behavior methods that return another VO construct it through its
normal validating path (single source of truth); when the target may legitimately
not exist for a valid input, the accessor is nullable (`tryFrom` semantics)
rather than throwing.

## Testing rigor (added v1.1.x)

- **Data-provider invariant tests.** Algebraic properties are covered with
  PHPUnit `#[DataProvider]` matrices (no PBT library): `Duration` plus-
  commutativity and `fromSeconds`/`toSeconds` round-trip, `Cidr` first/last-
  always-contained across prefixes, `IntRange` intersect-commutativity,
  `ComparableHelpers` min/max-agree-with-compareTo. **Gotcha:** the
  `#[DataProvider]` attribute must be imported (`use PHPUnit\Framework\
  Attributes\DataProvider;`) — without the import PHP resolves it to the test's
  own namespace, PHPUnit silently ignores it, and the strict flags surface it as
  an `ArgumentCountError` ("0 passed, N expected").
- **`min()`/`max()` are trait methods, not on the `Comparable` interface**, so a
  `Comparable`-typed variable cannot call them (PHPStan `method.notFound`).
  Invariant tests exercise them through concrete types, not a shared
  `Comparable` parameter. Adding them to the interface would be a breaking API
  change and is deliberately avoided.
- **Mutation testing** runs via `vendor/bin/infection` (needs a coverage driver
  — Xdebug locally, pcov in CI). The floor lives in `infection.json5`
  (`minMsi`/`minCoveredMsi`), so every run enforces it. See `known-issues.md`
  for the equivalent-mutant ignore policy.

## Generator-only dev dependencies are not framework dependencies

`symfony/intl` and `brick/money` sit in `require-dev` purely as inputs to
`tests/Support/IsoEnumGenerator.php`. Their data is baked into the committed
`src/I18n/*.php` enums, so nothing reaches the runtime. The rule forbidding
framework dependencies in the core (deptrac, and the grep in the bridge-split
plan) does not extend to them.

The split of currency digits matters: `symfony/intl` supplies CLDR *display*
digits (`displayFractionDigits()`), `brick/money`'s ISO 4217 table supplies
*minor units* (`isoMinorUnits()`). They disagree for 16 currencies — HUF, IDR
and COP report 0 in CLDR against 2 in ISO, IQD 0 against 3. Monetary arithmetic
and money persistence must use `isoMinorUnits()`; using the display value shifts
stored amounts by orders of magnitude. `tests/Unit/I18n/iso-minor-units.snapshot.json`
pins the full map so a coordinated dependency bump cannot change it silently.
