# Architecture

## Layers and the dependency rule

One layer, **Core**: everything under `src/`, with zero runtime dependencies (composer `require` is only
`php >=8.4`). `deptrac.yaml` fails the build if a class under `src/` touches `Doctrine\` or `Symfony\`; framework
integration lives in `msstc4php/vo-base-doctrine` and `msstc4php/vo-base-bundle`.

Those packages share the `MSSTC4PHP\ValueObject\` prefix (`…\Doctrine\`, `…\Bundle\`), so the core must never get a
`src/Doctrine/` or `src/Bundle/` directory: its classes would land in a sibling package's namespace.

## `Support/` is not a leaf

Besides the generic bases, `Support/` holds helpers owned by one family: `PackedAddress` (network arithmetic,
`@internal`) and the `UriComponents` trait (it also owns the private forbidden-character set shared by `Url` and
`Uri`), which imports `Network\QueryString`. That makes `Support` and
`Network` depend on each other; deptrac has a single `Core` layer and allows it. Moving the trait to `Network/`
would rename a public type, so it stays until a release that breaks anyway.

`Testing/RandomValueObjects` is public API for consumers' tests: seeded, dependency-free generators of valid
instances, experimental until 1.0. Library code never calls it; it is excluded from mutation testing, and
`testTheGeneratorSetIsDeliberate` plus a seed-42 golden test pin its surface and draw order.

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
