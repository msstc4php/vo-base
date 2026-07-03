# Integrations (bridges)

All bridges live under `src/Bridge/` and load only when their framework is
present (dependency direction Bridge → Core, enforced by deptrac).

## Doctrine ORM — `src/Bridge/Doctrine/Orm/`

- `AbstractStringVoType` — one generic DBAL `Type` mapping any
  `StringConvertible` VO to VARCHAR. A concrete type only names its VO class and
  (optionally) a column length. **DBAL 4:** do not implement `getName()` or
  `requiresSQLCommentHint()` — both removed; type identity is the registered name.
- 18 thin concrete string types + `PortType` (integer) + `GeoCoordinatesType`
  (single JSON column).
- `TypeRegistrar::registerAll()` — idempotent (guards with `Type::hasType`);
  `types()` returns the full name → class map. Bare Doctrine calls it at
  bootstrap; Symfony maps under `doctrine.dbal.types` instead.
- Backed enums (`CountryCode`/`CurrencyCode`/`LanguageCode`) use Doctrine's
  native `enumType` — no custom type.

## Doctrine MongoDB ODM — `src/Bridge/Doctrine/Odm/`

Same shape on the ODM `Type` base. Differences: converters take **no `$platform`
argument**, there is **no `getSQLDeclaration`**, each type `use ClosureToPHP`,
and `GeoCoordinatesType` stores an **embedded sub-document array**
(`['lat' => …, 'lng' => …]`) not JSON. Registration uses `Type::registerType`
(unconditional), guarded by `Type::hasType` for idempotency.

## Symfony Validator — `src/Bridge/Symfony/Validator/`

- `ValidValueObject` — generic constraint holding a `class-string<StringConvertible>`.
  Its validator delegates to the VO's own `from()` (catches
  `InvalidValueObjectException`) — **the single source of validation truth**;
  no rule is duplicated. Non-`final` because the per-VO attributes extend it.
- `ValidValueObjectValidator` — **constructor-argument-free** so the default
  factory can `new` it under standalone `Validation::createValidator()`. Skips
  `null`/`''` (leave to NotBlank/NotNull).
- Thin attributes: `AssertEmailAddress`, `AssertIpv4`, `AssertUuid`,
  `AssertUrl`, `AssertSlug`, `AssertPhoneNumber` (preset class + message).
