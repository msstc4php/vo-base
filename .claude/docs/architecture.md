# Architecture

## Layers and the dependency rule

One layer, **Core**: everything under `src/`, with zero runtime dependencies (composer `require` is only
`php >=8.4`). `deptrac.yaml` fails the build if a class under `src/` touches `Doctrine\` or `Symfony\`; framework
integration lives in `msstc4php/vo-base-doctrine` and `msstc4php/vo-base-bundle`.

Those packages share the `MSSTC4PHP\ValueObject\` prefix (`…\Doctrine\`, `…\Bundle\`, `…\Laravel\`, `…\Yii2\`,
`…\CodeIgniter4\`, `…\Cycle\`), so the core must never get a directory of one of those names under `src/`: its
classes would land in a sibling package's namespace.

## `Support/` is a leaf

`Support/` imports nothing from the families: besides the generic bases it holds `PackedAddress` (network
arithmetic, `@internal`), which `Network` uses. The `UriComponents` trait (it also owns the private
forbidden-character set shared by `Url` and `Uri`) imported `Network\QueryString` and moved to `Network/` in
1.0.0, which removed the only `Support` -> `Network` edge. deptrac has a single `Core` layer, so keeping it a leaf
is a review rule, not an enforced one.

`Testing/RandomValueObjects` is public API for consumers' tests: seeded, dependency-free generators of valid
instances, stable since 1.0.0: the generator set and return types follow semver, the values a seed draws do
not (a release may draw differently). Library code never calls it; it is excluded from mutation testing, and
`testTheGeneratorSetIsDeliberate` plus a seed-42 golden test pin its surface and draw order.

## `Payload/`: the storage form bridges share

`Payload\ValueObjectMapper` is the one place that decides how a value object is stored: string-backed
(`StringConvertible`) to string, `Abstract{Int,Float}ValueObject` to the number, backed enums to `->value`,
composites through a `PayloadCodec` to a flat array. Every bridge (Doctrine, Symfony Serializer, Laravel, Yii 2,
CodeIgniter 4, Cycle) calls it rather than re-detecting kinds, so a row written by one reads back through another.
JSON stays in the bridges. `PayloadCodec` is `@template-covariant`: `encode()` takes any `ValueObject` and throws
`LogicException` for a foreign class, which is what lets a `PayloadCodec<IntRange>` be passed where
`PayloadCodec<ValueObject>` is expected. The four codecs came from the Doctrine package in 1.1.0 with their formats
unchanged; `DateRangeCodec` keeps microseconds and the numeric offset on purpose (see its docblock).

Trust boundary: `fromPayload()` is for storage the application wrote, `fromUntrustedPayload()` for request input and
for raw values mass-assigned into a model. The latter refuses a class whose codec implements the marker
`TrustedSourceOnly` (only `BoundedStringCodec`: its payload carries `minLength`/`maxLength`, so a client could choose
them) with an `InvalidPayloadException`: the refusal is about the input, so a bridge reports it like invalid input
(422 / validation error), not a 500. Bridges never name `BoundedString`; they ask `supportsUntrusted()` up front
and `isComposite()` to decide on JSON, never re-detecting kinds. Reading is strict on both paths because
storage written by another bridge or by hand is not more trustworthy in format than a request: canonical integers,
plain decimals, finite floats, `DateRange` boundaries only with an offset (no `"now"`, no server-zone wall time).

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
