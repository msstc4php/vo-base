# Framework bridges: Symfony Serializer/HttpKernel, Laravel, Yii 2, CodeIgniter 4, Cycle ORM

Date: 2026-11-11 UTC. Requested by the owner: "1 + 2 + Yii 2 + CodeIgniter 4 + Cycle ORM".

## Why

The packages are for third parties. The core already works in any framework; what a framework user misses is the
glue where the framework turns raw input or a database column into an object and back: model casts, request
validation, serializer, route arguments. Without it every user writes the same adapter.

## Goals

1. `msstc4php/vo-base` 1.1.0: a public, framework-free **payload layer** every bridge shares, so a value stored by
   the Laravel cast reads back through the Cycle typecast and the Doctrine type alike.
2. `msstc4php/vo-base-doctrine` 1.1.0: uses the core payload layer; its `@internal` `Shared\Payload` copy goes.
3. `msstc4php/vo-base-bundle` 1.1.0: Symfony Serializer normalizer/denormalizer and a controller argument value
   resolver, both auto-detected like the existing groups.
4. New packages, each 0.1.0 (new integrations, so no stability promise yet):
   - `msstc4php/vo-base-laravel` — Eloquent cast, validation rule.
   - `msstc4php/vo-base-yii2` — validator, ActiveRecord attribute behavior.
   - `msstc4php/vo-base-codeigniter4` — Entity cast, Model (DataCaster) cast, validation rule set.
   - `msstc4php/vo-base-cycle` — Cycle ORM typecast handler.

## Non-goals

- Symfony Form type / DataTransformer (owner deferred it).
- Violation-collecting validation (declined 2026-11-10 UTC); every bridge reports one failure per field.
- Yii 3, Laminas, Slim, Nette, CakePHP, Spiral-specific glue beyond Cycle ORM.
- Multi-column money in the new bridges. Money stays the Doctrine package's business.
- Packagist publication (separate step, owner's UI).

## Anti-goals

- A bridge that silently turns an invalid stored value into null. Invalid input fails loudly, the same way the
  Doctrine types do.
- Six slightly different storage formats for the same value object.

## The core payload layer (`MSSTC4PHP\ValueObject\Payload\`)

"Payload" = the storage form of a value object: a scalar (`string|int|float`) or, for a composite, a flat
`array<string, scalar|null>`.

| Kind | Detected by | Payload | Read back from |
|---|---|---|---|
| string-backed | `instanceof StringConvertible` | `(string) $vo` | `string` |
| int-backed | `AbstractIntValueObject` subclass | `$vo->value` | `int`, or a decimal-digit string (PDO returns strings) |
| float-backed | `AbstractFloatValueObject` subclass | `$vo->value` | `int\|float`, or a numeric string |
| backed enum | `BackedEnum` (`CountryCode`, `CurrencyCode`, ...) | `->value` | `int\|string` |
| composite | a registered `PayloadCodec` | `array` | `array` |

- `PayloadCodec<T>` interface plus the four codecs moved from the Doctrine package (`BoundedStringCodec`,
  `IntRangeCodec`, `GeoCoordinatesCodec`, `DateRangeCodec`), now public API, formats unchanged byte for byte.
- `ValueObjectMapper` (final, stateless): `supports(class-string): bool`, `toPayload(object): payload`,
  `fromPayload(class-string<T>, mixed): T`. JSON is the bridge's concern; the core never encodes it.
- Failures: the value object's own `InvalidValueObjectException` for invalid content; the new
  `Exception\InvalidPayloadException` (extends `InvalidValueObjectException`) for a wrong raw type or shape.
  A bridge that catches `InvalidValueObjectException` therefore catches both. `LogicException` for a class the
  mapper does not support (programmer error, not input error).

## Shared conventions for the new packages

- Package `msstc4php/vo-base-<framework>`, namespace `MSSTC4PHP\ValueObject\<Framework>\` (`Laravel`, `Yii2`,
  `CodeIgniter4`, `Cycle`), tests `...\Tests\` like the core and the Doctrine package.
- Require `php >=8.4`, `msstc4php/vo-base ^1.1`, and the framework component(s) as hard requirements: the
  framework is the whole point of the package.
- Tool configuration byte-identical to `msstc4symfony/bundle-standard` v4.1.0 `php84` templates; `checks.yml`
  modelled on `vo-base-doctrine` (templates, check, phpunit 8.4/8.5 highest + 8.4 lowest, bc-check, infection on
  main). `composer.json` / `composer-ci.json` / gitignored `composer-local.json` (path repo `../vo-base`, alias
  `dev-main as 1.1.0`) as in the siblings.
- Docs: README (install via VCS repos until Packagist, usage), CHANGELOG `0.1.0`, SECURITY.md, LICENSE (copy),
  CLAUDE.md, `.claude/docs/` (README, architecture, integrations, known-issues, conventions).
- Every bridge uses `ValueObjectMapper`; none re-implements kind detection.
- Tests use the real framework component, not mocks, wherever it runs without a full application (SQLite in
  memory for database round trips).

## Per package

**Symfony bundle** — `ValueObjectNormalizer` (normalize + denormalize via the mapper; composites become arrays,
so `BoundedString` keeps its bounds, unlike `jsonSerialize()`), registered when `symfony/serializer` is installed;
`ValueObjectValueResolver` for controller arguments typed with a supported string/int/float value object (enums are
already handled by Symfony's own resolver), registered always (HttpKernel is required). Config nodes
`serializer` and `value_resolver`, same null/true/false semantics as `validator`. Invalid input in the resolver
is a 404 for a route attribute, matching Symfony's enum resolver.

**Laravel** — `Casts\AsValueObject` (`CastsAttributes` + `Castable`, `AsValueObject::of(EmailAddress::class)`);
composites stored as JSON. `Rules\ValidValueObject` (`ValidationRule`). Support `illuminate/* ^12.0 || ^13.0`.

**Yii 2** — `Validator\ValueObjectValidator` (`class` property), `Behavior\ValueObjectAttributeBehavior`
(attribute => class map; hydrates after find, dehydrates before save, restores after save). `yiisoft/yii2 ~2.0.52`
(PHP 8.4 support); the bower/npm asset requirements are satisfied in the dev manifests only.

**CodeIgniter 4** — `Entity\ValueObjectCast` (Entity cast handler), `DataCaster\ValueObjectCast` (Model field
cast, CI >= 4.5), `Validation\ValueObjectRules` (`valid_value_object[FQCN]`). `codeigniter4/framework ^4.5`.

**Cycle ORM** — `Typecast\ValueObjectTypecast` (`CastableInterface` + `UncastableInterface`): claims every column
whose typecast rule is a supported value-object class, leaves the rest to the next handler. `cycle/orm ^2.10`.

## Release order

core 1.1.0 → doctrine 1.1.0 → bundle 1.1.0 → the four new packages (each needs its empty GitHub repository first,
created by the owner). Push and tags only with the owner's go-ahead.

## Calls made without the owner (reversible)

- Payload layer in the core rather than copies per bridge.
- New packages start at 0.1.0, not 1.0.0.
- `vo-base-codeigniter4` and `vo-base-yii2` carry the framework major in the name (CI3/Yii 3 are different APIs).
