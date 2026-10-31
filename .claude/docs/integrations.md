# Integrations

The core has none. It depends on `php >= 8.4` and nothing else, and that is
enforced by `deptrac.yaml`: any class under `src/` that touches `Doctrine\` or
`Symfony\` fails the build.

Framework support lives in two separate packages, each with its own
`.claude/docs/`:

| Package | What it provides |
|---|---|
| `msstc4php/vo-base-doctrine-bridge` | 37 Doctrine field types per branch (ORM and MongoDB ODM), plus base and high-precision money embeddables |
| `msstc4symfony/vo-base-bridge` | Symfony Validator constraints and a bundle that registers both them and the Doctrine types |

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
