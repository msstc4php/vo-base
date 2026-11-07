# Changelog

## Unreleased

- Immutable value objects with no framework dependencies. Doctrine and Symfony integration moved to
  `msstc4php/vo-base-doctrine-bridge` and `msstc4symfony/vo-base-bridge`.
- `brick/math` support starts at 0.14.2 (declared as a conflict below it): `Decimal` and `Percentage`
  arithmetic relies on PascalCase `RoundingMode` cases. The earlier `^0.12 || ^0.13` range never worked.
- Currency codes and CLDR display digits come from `symfony/intl` 7.4.22. Against the unreleased snapshot this
  drops `CurrencyCode::CNX` and moves `PKR` display digits from 0 to 2; ISO minor units (from `brick/money`) are
  unchanged.
