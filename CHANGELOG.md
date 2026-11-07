# Changelog

## 0.2.0 - 2026-11-07

**Breaking:** `CurrencyCode::isoMinorUnits()` returns a value instead of `null` for 47 codes, so code that used
`null` to reject withdrawn currencies starts accepting them.

- `brick/math` and `brick/money` 0.15 up to `brick/math` 1.0 are supported; both are declared as conflicts below 0.15
  so consumers cannot hold an ISO table older than the enum's.
- ISO 4217 data comes from `brick/money` 0.15.2. Withdrawn codes (ADP, BEF, ATS, ...) and the current `XCG` now
  report their ISO minor units instead of `null`; `null` remains where the ISO data has no value (`CNH`, `XEU`, ...).
- `CurrencyCode::XAD` is added; CLDR does not know it yet, so its display digits fall back to ISO minor units.

## 0.1.0 - 2026-11-07

- Immutable value objects with no framework dependencies. Doctrine and Symfony integration moved to
  `msstc4php/vo-base-doctrine-bridge` and `msstc4symfony/vo-base-bridge`.
- `brick/math` support starts at 0.14.2 (declared as a conflict below it): `Decimal` and `Percentage`
  arithmetic relies on PascalCase `RoundingMode` cases. The earlier `^0.12 || ^0.13` range never worked.
- Currency codes and CLDR display digits come from `symfony/intl` 7.4.22. Against the unreleased snapshot this
  drops `CurrencyCode::CNX` and moves `PKR` display digits from 0 to 2; ISO minor units (from `brick/money`) are
  unchanged.
