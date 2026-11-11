# Integrations

The core has none. It depends on `php >= 8.4` and nothing else, and that is
enforced by `deptrac.yaml`: any class under `src/` that touches a bridged framework's namespace (`Doctrine\`,
`Symfony\`, `Illuminate\`, `yii\`, `CodeIgniter\`, `Cycle\`, `Spiral\`) fails the build.

Framework support lives in separate packages, each with its own
`.claude/docs/`, all sharing the core's `Payload\ValueObjectMapper`:

| Package | What it provides |
|---|---|
| `msstc4php/vo-base-doctrine` | 38 Doctrine field types per branch (ORM and MongoDB ODM), plus base and high-precision money embeddables |
| `msstc4php/vo-base-bundle` | Symfony: Validator constraints, Serializer normalizer, route argument resolver, Doctrine type registration |
| `msstc4php/vo-base-laravel` | Laravel 13 Eloquent cast + validation rule (Laravel 12 blocked: its brick/math cap is below the core's 0.15 floor) |
| `msstc4php/vo-base-yii2` | Yii 2 validator + ActiveRecord behavior (+ trait restoring VOs after a failed save) |
| `msstc4php/vo-base-codeigniter4` | CodeIgniter 4 Entity/Model casts + `valid_value_object[...]` rule |
| `msstc4php/vo-base-cycle` | Cycle ORM 2 typecast handler (`untrustedCastInput` opt-in for `make(typecast: true)` with request data) |

## Optional runtime libraries

Two packages are optional at runtime and degrade explicitly rather than
silently:

- **`brick/math`** — arithmetic on `Decimal` and `Percentage`. Without it those
  methods throw `MissingDependencyException`; construction and comparison still
  work.
- **`brick/money`** — pairs with `CurrencyCode` for full money handling. The
  core never touches it; the Doctrine bridge does.

## Generator inputs are not dependencies

`symfony/intl` and `brick/money` sit in `require-dev` purely as inputs to
`tests/Support/IsoEnumGenerator.php`. Their data is baked into the committed
`src/I18n/*.php` enums, so nothing reaches the runtime, and the deptrac rule
above does not apply to a build-time table. See `conventions.md`.
