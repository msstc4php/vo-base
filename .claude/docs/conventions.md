# Conventions (project-specific)

- **Construction convention:** every VO exposes `from()` (throws),
  `tryFrom()` (nullable), `isValid()` (bool), mirroring `BackedEnum`. Scalar
  bases provide these; bespoke VOs replicate them.
- **`isValid()`/`tryFrom()` are typed, not `mixed`.** The plan suggested
  `mixed` signatures; we use the concrete scalar type (`string`/`int`) to keep
  PHPStan max clean. The Symfony validator does the `is_string` guard before
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

## Tooling (aligned with msstc4php/healthcheck-bundle)

- **PHPStan level 9**, not `max`, plus the extension-installer stack
  (strict-rules, symfony, doctrine, phpunit, beberlei-assert, disallowed-calls).
  Config is `phpstan.dist.neon` (not auto-detected — always pass `-c`);
  `phpstan-ci.neon` sets `reportUnmatchedIgnoredErrors: false`; the baseline is
  empty. `treatPhpDocTypesAsCertain: false`.
- **PHP-CS-Fixer `@Symfony`** ruleset (+ `declare_strict_types`, left-aligned
  phpdoc, one-space concat, post-increment, no Yoda). Run `vendor/bin/php-cs-fixer
  check` (the Makefile `check` target does).
- **Rector** uses the bundle's prepared sets + skip list, but Symfony-app-only
  rules and `rector-symfony`/`doctrine` code-quality sets are dropped (we are a
  library, not an app). `AddSeeTestAnnotationRector` is skipped (noise).
- **`beStrictAboutCoverageMetadata` is OFF.** A VO library's tests legitimately
  construct collaborator VOs as inputs, which the strict flag reports as risky
  ("executed code not listed as covered"). Other strict flags (`failOnRisky`,
  `failOnWarning`, `failOnPhpunitDeprecation`) stay on. Coverage-only test
  helpers (e.g. `IsoEnumsUpToDateTest`) use `#[CoversNothing]`.
- **CI:** `.gitlab-ci.yml` (primary) mirrors `make check` + phpunit + a
  core-only leg; `.github/workflows/ci.yml` also present. Rename/relocate the
  Symfony-app-shaped bits when copying more from the bundle.
- Toolchain gate: `make check` = php -l + PHPStan (lvl 9) + PHP-CS-Fixer +
  composer validate/audit + Rector + deptrac; `make test` runs PHPUnit. Run both
  before considering a change done.
