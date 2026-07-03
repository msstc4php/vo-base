# Known issues, quirks, and deliberate scope cuts

## Framework quirks

- **DBAL 4 migration diff-noise.** DBAL 4 removed the `(DC2Type:…)` column-comment
  mechanism, so `doctrine/migrations` can no longer distinguish custom-typed
  columns that share a SQL declaration — it may emit repeated no-op migrations
  for VO columns. Cannot be fixed in-library; give a VO column a distinctive
  declaration where it matters, or ignore the diff noise. (dbal #6257/#7327,
  migrations #1435/#1441.)
- **PHP 8.4:** a `readonly` property cannot declare property hooks, and a purely
  virtual hooked property cannot be `readonly` — choose per property. Implicitly
  nullable params (`Foo $x = null`) are deprecated; always write `?Foo $x = null`.
- **ODM `Type::registerType` is unconditional** (overwrites), unlike DBAL's
  `addType`. The ODM `TypeRegistrar` guards with `Type::hasType` for idempotency.

## Quirks in this codebase

- **Reserved words as enum case names.** Generated `CountryCode`/`LanguageCode`
  cases include `DO`, `AS`, `OR` (Dominican Republic, American Samoa, Odia).
  PHP 8.4 accepts reserved keywords as enum case names, so this is fine.
- **`GeoCoordinates` persistence** is a single JSON column (ORM) / embedded array
  (ODM), **not** a Doctrine embeddable. An embeddable would need ORM mapping
  metadata on the class, which would couple Core to Doctrine and break the
  autoload-safety rule. Map it as an embeddable in the consuming app if
  sub-field querying is needed. (Deviation from the plan's R10.)

## Deliberate scope cuts (follow-up work)

- **Doctrine types cover the string VOs + `Port` + `GeoCoordinates`.**
  `Base64String`/`JsonString` have no bundled type yet (text-heavy). Adding any
  missing VO type is one thin subclass of `AbstractStringVoType` plus a line in
  the `TypeRegistrar` — the generic base does the work.
- **Symfony `Assert*` attributes** ship for a representative six
  (Email/Ipv4/Uuid/Url/Slug/PhoneNumber). Any other VO already works via the
  generic `ValidValueObject(<VoClass>::class)`; add a thin attribute if a
  dedicated one is wanted.
- Financial family (`Iban`/`Bic`/`CreditCardNumber`/`Decimal`), `Cidr`/`IpRange`,
  `HttpMethod`, temporal VOs, and `brick/money` interop are deferred to v1.1
  (see the plan's Scope Boundaries).
