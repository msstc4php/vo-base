# Architecture

## Layers and the dependency rule

Two layers, enforced by `deptrac.yaml`:

- **Core** — everything under `src/` except `src/Bridge/`. Zero runtime
  dependencies (composer `require` is only `php >=8.4`).
- **Bridge** — `src/Bridge/`. May depend on Core and on the optional frameworks
  Framework packages are gone entirely; deptrac fails the build if a class
  under `src/` touches `Doctrine\` or `Symfony\`.

**Core must never reference a Bridge class.** This is the autoload-safety
invariant: bridge classes `extends` framework base classes, so they fatal if
loaded without the framework. They are safe only because nothing in Core (and
no always-loaded path) touches them — they are reached exclusively from
consumer bootstrap after the consumer installs the framework. `deptrac`
(`make check`) fails the build if Core → Bridge appears.

## The value object base machinery

`src/Support/Abstract{String,Int,Float}ValueObject.php` implement the whole
value object contract once — `from()`, `tryFrom()`, `isValid()`, `equals()`,
`compareTo()` (int/float), `__toString()`, `jsonSerialize()` — around a single
subclass hook:

```php
abstract protected function parse(<scalar> $value): <scalar>; // validate + return canonical form
```

A concrete VO declares only its rule. Normalization (lowercasing, separator
canonicalization) happens inside `parse()` by returning the canonical form.
Construction throws a per-VO exception extending `InvalidValueObjectException`.

Value objects with extra constructor arguments or non-scalar backing
(`BoundedString`, `IntRange`, `GeoCoordinates`, `Uuid`, `ClassString`) are
bespoke `final readonly` classes implementing `Contract\ValueObject` directly.

## Contracts (`src/Contract/`)

- `ValueObject` — `equals()` + `JsonSerializable`.
- `Comparable` — `compareTo()`; implemented by the numeric bases and `SemVer`.
- `StringConvertible` — `extends Stringable` + static `from(string): static`.
  This is the seam the Doctrine bridge package uses to map ANY string-backed VO
  generically (one abstract Type covers all of them). Implemented by
  `AbstractStringValueObject`, `Uuid`, and `ClassString`.
