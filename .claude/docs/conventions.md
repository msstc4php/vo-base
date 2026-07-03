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
- Toolchain gate: `make check` = PHPStan max + PHP-CS-Fixer + Rector + deptrac +
  PHPUnit. Run it before considering a change done.
