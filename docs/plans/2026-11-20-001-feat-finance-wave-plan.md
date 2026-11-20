# Direction 3, plan 2: `vo-base` 1.3.0 and wave 2, `vo-finance` and `vo-finance-doctrine` 0.1.0

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** First, the `vo-base` changes of wave 2: the IBAN registry structure and the `CreditCardNumber`
deprecation, released as `vo-base` and `vo-base-doctrine` 1.3.0. Then the finance package and its Doctrine bridge,
released as 0.1.0. The package covers:
- securities and entity identifiers;
- payment-card numbers with IIN ranges;
- payment QR payloads: EMVCo and national profiles, EPC, Swiss QR-bill, GOST R 56042, SBP, UPI.

**Architecture:** Every value object is a string value object (`AbstractStringValueObject`), including the payment QR
payloads. Their canonical string is the encoded payload, so `ValueObjectMapper` and every bridge handle them with no
codec. The shared algorithms live in `Internal\` helpers: check digits, EMVCo TLV and CRC, and the IIN table.

**Tech Stack:** PHP 8.4, `msstc4php/vo-base ^1.2`, PHPUnit, PHPStan max, Infection.

**Spec:** `vo-base/docs/brainstorms/2026-11-19-identifiers-and-codes-requirements.md`. Where this plan says nothing,
the conventions fixed by plan 1 (`2026-11-19-001-feat-gs1-wave-plan.md`) and by `vo-gs1` as built apply.

## Global Constraints

- **Sources.** A check digit or a structural rule is enforced only when it is quoted from an official source:
  - the issuing body's or registration authority's own publication;
  - a regulator's or central bank's act;
  - a standard cited by number, paid ones included;
  - an unmodified Internet Archive copy of any of these.

  An aggregator counts only "as quoted from" such a source. Otherwise the class checks the format only, and the
  README's "format only" list gives the reason.
- **Validity.** Never refuse a value that is still valid.
- **Registries.** Registry-backed data is used only to decode, never to refuse: IIN ranges, MIC, CFI tables, country
  prefixes. A well-formed value outside the table decodes as unknown.
- **Output and masking.**
  - `__toString()` and `jsonSerialize()` give the full value, card numbers included (owner's decision).
  - vo-base `Contract\Maskable` (`masked()`) is implemented by every value object that carries an account or a
    card: `PaymentCardNumber` and every payment QR payload.
  - Securities and entity identifiers get no `masked()`.
- **Exception messages** never contain the input or any part of it. Never call `InvalidValueObjectException::excerpt()`
  in the new packages.
- **Factories** never pick silently. A value that fits several types throws vo-base `Exception\AmbiguousValueException`
  with the candidates.
- **Citations.** Every class cites its source with `@see` (the template cs-fixer rewrites `@link`). Tests use the
  source's own examples where it has them.
- **New packages.**
  - `msstc4php/vo-finance`: namespace `MSSTC4PHP\ValueObject\Financial\`; `require`: `php >=8.4`,
    `msstc4php/vo-base ^1.2`.
  - `msstc4php/vo-finance-doctrine`: namespace `MSSTC4PHP\ValueObject\Financial\Doctrine\`; `require`:
    `msstc4php/vo-finance ^0.1`, `msstc4php/vo-base-doctrine ^1.2`.
  - Scaffold, CI, `composer-ci.json`, `.claude/docs` and `CLAUDE.md`: copied from `vo-gs1` / `vo-gs1-doctrine`.
  - Tool configs are byte-identical to the templates and never edited.
  - Gates: PHPStan max + `phpstan-ci.neon`, cs-fixer, rector, PHPUnit, Infection >= 75% covered MSI, lowest-deps job.
- **Commits and releases.**
  - Commit identity `Maxim Shamaev <maxim.shamaev@gmail.com>`, trailer `Co-Authored-By: Claude Opus 5.5
    <noreply@anthropic.com>`.
  - Commits and pushes are made without asking.
  - Tags are made after green CI and with the owner's confirmation. The owner deferred releases (2026-11-20), so the
    release steps wait for their word.
  - The GitHub repositories and Packagist are the owner's.
- **Style.** Code comments in English, minimal. Strict PHPStan types. Dates in UTC.

## Decisions this plan makes

- **`vo-base` 1.3.0:**
  - `Finance\CreditCardNumber` gets a `@deprecated` docblock pointing to
    `MSSTC4PHP\ValueObject\Financial\Card\PaymentCardNumber` (`msstc4php/vo-finance`). Its behaviour is unchanged,
    and it is removed no earlier than `vo-base` 2.0.
  - There is no runtime `E_USER_DEPRECATED`: it would fire on every hydration from the database.
  - `vo-base-doctrine` marks its credit-card types `@deprecated` the same way.
- **IBAN registry (`vo-base` 1.3.0).** `Finance\Iban` checks the length and the BBAN character pattern of every
  country in the SWIFT IBAN Registry. SWIFT is the ISO 13616 registration authority.
  - A country absent from the registry keeps today's generic check, never a refusal.
  - New accessors: `bankIdentifier(): ?string` and `branchIdentifier(): ?string`, from the registry's positions;
    `isSepaCountry(): ?bool`.
  - A tool, `tools/generate-iban-registry.php`, writes `src/Internal/IbanRegistry.php` from the registry's TXT
    edition. The edition is recorded in the header.
  - The licence is checked first. If redistribution is not allowed, only the facts are embedded (length, pattern,
    positions), typed from the registry, and the README says so.

  This is stricter than 1.2: an IBAN of a registered country with the wrong length now fails. Such a value is not an
  IBAN under ISO 13616. The CHANGELOG lists it under "Changed".
- **`vo-finance` does not require `vo-base` 1.3.** It reads what it needs from IBAN strings itself, for example the
  Swiss QR-IID at positions 5-9. Its CI is therefore not blocked by the deferred vo-base release.
- **Doctrine type names:** `fin_<snake_name>` (`fin_isin`, `fin_payment_card_number`, `fin_emvco_merchant_qr`).
  - The column length is the class's maximum length.
  - Above 4000 characters the type is the text type, as in `vo-gs1-doctrine`.
- **Exceptions** in `Financial\Exception\`, all extending vo-base `InvalidValueObjectException`:
  `InvalidSecurityIdentifierException`, `InvalidPaymentCardNumberException`, `InvalidPaymentQrException`,
  `InvalidPaymentReferenceException`.
- **Securities and entities** (`Financial\Security\`):
  - `Isin` (ISO 6166): 2-letter prefix, 9-character NSIN, check digit.
    - The prefix is never refused for missing from ISO 3166: `XS`, `EU` and other non-country prefixes exist.
    - `prefix()`, `countryCode(): ?CountryCode`, `nsin()`, `fromNsin(string $prefix, string $nsin)`.
    - `cusip(): ?Cusip` only where the source says the NSIN is a CUSIP (US, CA), and `sedol(): ?Sedol` likewise
      (GB, IE).
  - `Lei` (ISO 17442): 20 characters with the ISO 7064 MOD 97-10 check pair. `louPrefix()`.
  - `Upi` (ISO 4914): the structure and check character as ANNA DSB publishes them.
  - `Cfi` (ISO 10962): 6 letters, `X` for "not applicable".
    - `category(): ?CfiCategory` and `group(): ?string` decode from the edition's table if it is reachable.
    - The class accepts any 6 letters; an unknown code decodes as null.
  - `Fisn` (ISO 18774): issuer short name, `/`, instrument description, with the lengths and character set of the
    standard.
  - `Mic` (ISO 10383): 4 characters, format only.
    - No embedded list: it changes monthly, and the operating/segment distinction needs that list.
    - Cost: no `operatingMic()`.
  - `Figi` (OMG FIGI): 12 characters, `G` third, no vowels, the prefixes the spec excludes, check digit.
  - `Cusip`: 9 characters, check digit; `*`, `@` and `#` as the CUSIP rules allow.
  - `Sedol`: 7 characters, the London Stock Exchange's weights and alphabet.
  - `Wkn` and `Valor`: format only, unless the research finds a published check.
  - `SecurityIdentifierFactory::fromString()`: returns the one class that accepts the value, or throws
    `AmbiguousValueException`. A 12-character value can be both an ISIN and a FIGI; 6 digits can be both a WKN and a
    Valor.
- **Payment cards** (`Financial\Card\`):
  - `PaymentCardNumber implements Maskable`: ISO/IEC 7812-1, at most 19 digits; the minimum is the standard's.
    - The Luhn check is enforced, except for a range whose scheme's own publication states that its numbers have no
      Luhn digit. In that case the check is skipped for that range only.
    - Spaces and hyphens are stripped, as in the old class.
    - Accessors:
      - `iin(): string`: the first 8 digits, per ISO/IEC 7812-1:2017;
      - `last4()`;
      - `schemes(): list<CardScheme>`: a list, because co-badged and overlapping ranges exist;
      - `matchesSchemeLength(): bool`.
    - `masked()` keeps the first 6 and the last 4 digits, the most PCI DSS 4.0 req. 3.4.1 allows for every length.
    - A length outside a scheme's published lengths is not refused, because schemes add lengths (Visa 19). Cost: a
      15-digit "Visa" is accepted.
  - `CardScheme` enum, with `lengths(): list<int>`.
  - `Internal\IinRanges` is typed from each scheme's own publication; every range cites it. Discover Global
    Network's IIN table covers Discover, Diners, JCB, UnionPay, RuPay and others; Mastercard, Visa, Amex and Mir
    publish their own. A scheme with no reachable publication is not listed, so its numbers decode as an empty list.
- **EMVCo merchant-presented QR** (`Financial\PaymentQr\Emvco\`), per EMVCo QRCPS-MPM:
  - `MerchantPresentedQr`: the TLV payload. Two-digit ID, two-digit length; templates 26-51 and 62 are nested.
    - The CRC (ID 63) is CRC-16/CCITT-FALSE over the payload through `6304`, written in hex; the parser accepts
      either case of the hex digits.
    - Accessors:
      - `pointOfInitiation(): ?PointOfInitiation` (static `11`, dynamic `12`);
      - `merchantAccounts(): list<MerchantAccount>` (ID, GUI, sub-fields);
      - `merchantCategoryCode()`;
      - `currency(): ?CurrencyCode`, through vo-base `fromNumeric`;
      - `amount(): ?Decimal`;
      - `countryCode(): ?CountryCode`;
      - `merchantName()`, `merchantCity()`, `postalCode()`;
      - `additionalData(): array<string, string>`;
      - `get(string $id): ?string`.
    - The canonical string is the payload as given; it is already exact.
  - `MerchantPresentedQrBuilder`: immutable `with(string $id, string $value)` and `withMerchantAccount(...)`;
    `build()` orders the IDs and computes the CRC.
  - `masked()` masks the values of the merchant-account templates; the other fields are left visible.
- **National EMVCo profiles** (`Financial\PaymentQr\<Country>\`).
  - One class per profile that publishes a spec. Each is accepted only when its spec's mandatory fields and GUI are
    present. Each exposes the fields that the spec defines: for example the Pix key and `txid`, or the PromptPay
    proxy type and value.
  - The spec's list: Pix BR Code (BCB), PromptPay / Thai QR (BOT), SGQR (MAS/IMDA), DuitNow (PayNet), QRIS (Bank
    Indonesia).
  - Found at planning (my call): VietQR (NAPAS), KHQR (National Bank of Cambodia), HK FPS (HKMA), JPQR (Payments
    Japan), QR Ph (BSP), Bharat QR (NPCI).
  - A profile whose spec is unreachable is not built. It is listed in the README, and its payloads parse as the
    generic `MerchantPresentedQr`.
  - `PaymentQrFactory::fromString()`: returns the profile class that matches. With no match it returns
    `MerchantPresentedQr`. When several profiles match, for example SGQR carrying PayNow, it throws
    `AmbiguousValueException`.
- **Other payment payloads** (`Financial\PaymentQr\`):
  - `Epc\EpcQr` (EPC069-12, the current version plus `001`): the line format `BCD`, version, charset, `SCT`, BIC,
    name, IBAN (vo-base `Iban`), amount `EUR…`, purpose, reference or text.
  - `Swiss\QrBill` (SIX Implementation Guidelines QR-bill, current version):
    - `SPC`, `0200`, the structured addresses;
    - QR-IBAN with `QRR`, IBAN with `SCOR` or `NON`;
    - the trailer `EPD` and the billing information.
  - `Reference\QrReference`: 27 digits, recursive mod 10. `Reference\CreditorReference`: ISO 11649 `RF` with mod
    97. Both are value objects of their own and are typed parts of the QR-bill.
  - `Ru\Gost56042Payload` (GOST R 56042-2014):
    - the header `ST0001` + charset digit, then `|`-separated `Key=Value` pairs;
    - the mandatory `Name`, `PersonalAcc`, `BankName`, `BIC`, `CorrespAcc` with their formats;
    - the account control key against the BIC, if the Bank of Russia's rule is reachable.
  - `Ru\SbpPaymentLink`: the `https://qr.nspk.ru/<id>` link with its query fields, as far as NSPK publishes them;
    otherwise format only (host, id shape).
  - `India\UpiPaymentUri`: `upi://pay?…` per NPCI's UPI linking specification (`pa`, `pn`, `am`, `cu`, `tr`, …).
- **Line endings and character sets.** The canonical form must round-trip losslessly (anti-goal), so:
  - EPC and Swiss payloads keep their line separators as given (LF or CRLF); equality compares the canonical string;
  - `fromString()` takes UTF-8 text only;
  - a payload declared in another charset is accepted through `fromBytes(string $bytes)`, which transcodes it and
    rewrites the charset field to its UTF-8 code (EPC `1`, GOST `2`). Cost: the stored bytes differ from the
    printed ones in that field.

## Review Focus

1. A card number with spaces, a 19-digit UnionPay number, a number with a CVV typed after it:
   - no digit appears in any message;
   - `masked()` is `first6…last4`;
   - a co-badged range gives two schemes.
2. An EMVCo payload with:
   - a wrong CRC;
   - a lowercase CRC;
   - a TLV length that runs past the end of the payload;
   - a nested template 62 with a length past its parent.

   Each is refused with a message, never an out-of-bounds read or a notice. A lowercase CRC with the right value is
   accepted.
3. A Swiss QR-bill and an EPC QR with CRLF and with LF line endings, and with a trailing newline. Each round-trips
   byte-exact, and the two forms are not equal.
4. Securities:
   - `us0378331005` (lowercase) gives `US0378331005`;
   - a 12-character value valid as both an ISIN and a FIGI makes the factory throw `AmbiguousValueException`;
   - a CUSIP with `*`, `@`, `#`.
5. IBAN: a German IBAN of 21 characters is refused; an IBAN of a country not in the registry with a valid checksum is
   accepted; every existing `Iban` test stays green.

---

### Task 1: `vo-base` 1.3 changes

**Files:**
- `vo-base/tools/generate-iban-registry.php`, `src/Internal/IbanRegistry.php` (generated);
- `src/Finance/{Iban,CreditCardNumber}.php`;
- `docs/sources/IBAN.md`;
- tests, `CHANGELOG.md` (`Unreleased`), `README.md`;
- `vo-base-doctrine`: `@deprecated` on its credit-card types, `CHANGELOG.md` (`Unreleased`).

**Interfaces:** Produces the `Iban` accessors above.

- [ ] Research, quoted into `docs/sources/IBAN.md`: the current edition of the SWIFT IBAN Registry, its TXT layout,
  and its terms of use.
- [ ] Tests:
  - Review Focus 5;
  - every registry example IBAN passes and gives its bank and branch identifiers;
  - the generator is deterministic;
  - `CreditCardNumber` behaves as before.
- [ ] Gates in both repos; commit `feat: IBAN registry structure; deprecate CreditCardNumber` (vo-base) and
  `chore: deprecate the credit-card types` (vo-base-doctrine); push; green CI. Release 1.3.0 of both waits for the
  owner.

### Task 2: Scaffold, sources, check-digit helpers

**Files:**
- `vo-finance/` and `vo-finance-doctrine/` scaffold;
- `vo-finance/docs/sources/FINANCE.md`;
- `src/Internal/{Luhn,Iso7064Mod97,IsinCheckDigit,CusipCheckDigit,SedolCheckDigit,FigiCheckDigit,Crc16CcittFalse,Mod10Recursive}.php`;
- tests.

**Interfaces:** Each helper has `compute(string $body): string` and `isValid(string $value): bool`.

- [ ] Research into `FINANCE.md`, with a quote and a link per rule. Cover every source this plan names:
  - ISO 6166, 17442, 4914, 10962, 18774, 10383, through ANNA, GLEIF, ANNA DSB and SWIFT publications;
  - OMG FIGI;
  - CUSIP Global Services;
  - LSE SEDOL;
  - WKN and Valor;
  - ISO/IEC 7812-1 and each card scheme's IIN publication, including any no-Luhn range;
  - PCI DSS 4.0 3.4.1;
  - EMVCo QRCPS-MPM;
  - each national profile listed above;
  - EPC069-12;
  - SIX QR-bill IG;
  - ISO 11649;
  - GOST R 56042-2014 and the Bank of Russia account-key rule;
  - NSPK SBP;
  - NPCI UPI linking specification.

  Record which sources are unreachable; those classes become format only, or the profile is dropped.
- [ ] If the GitHub repositories do not exist yet, ask the owner to create `msstc4php/vo-finance` and
  `msstc4php/vo-finance-doctrine`; work on local `main` meanwhile.
- [ ] Tests:
  - each helper with its source's own example: the ISIN `US0378331005`, the LEI from GLEIF, the CUSIP `037833100`,
    the SEDOL `0263494`, a FIGI from OMG, and the CRC check value `29B1` of `123456789`;
  - the QR reference and creditor reference examples of SIX and ISO 11649.
- [ ] Gates; commit `feat: scaffold, finance sources and check digits`; push when the repo exists.

### Task 3: Securities and entity identifiers

**Files:** `src/Security/{Isin,Lei,Upi,Cfi,CfiCategory,Fisn,Mic,Figi,Cusip,Sedol,Wkn,Valor,SecurityIdentifierFactory}.php`,
tests.

- [ ] Tests:
  - Review Focus 4;
  - per class, its source's examples, and a wrong check character refused with a message naming only the type;
  - `Isin::fromNsin('US', '037833100')` gives `US0378331005`, and `->cusip()` gives that CUSIP;
  - an `XS` ISIN is accepted with `countryCode()` null;
  - `Cfi` `ESVUFR` decodes its category (equities); an unknown 6-letter CFI is accepted with null decoding;
  - a FISN over the length limits is refused.
- [ ] Gates; commit `feat: securities and entity identifiers`.

### Task 4: Payment cards

**Files:** `src/Card/{PaymentCardNumber,CardScheme}.php`, `src/Internal/IinRanges.php`, tests.

- [ ] Tests:
  - Review Focus 1;
  - one number per `CardScheme` case, built with a valid Luhn digit inside its cited range;
  - a number in no known range gives an empty `schemes()`;
  - Mastercard `2221…` and `2720…` (the edges of the 2-series), Mir `2200…`–`2204…`;
  - the old `CreditCardNumber` and the new class agree on every number the old one accepts, except numbers too short
    for ISO/IEC 7812.
- [ ] Gates; commit `feat: payment card numbers and IIN ranges`.

### Task 5: EMVCo merchant-presented QR

**Files:** `src/PaymentQr/Emvco/{MerchantPresentedQr,MerchantPresentedQrBuilder,MerchantAccount,PointOfInitiation}.php`,
`src/Internal/EmvcoTlv.php`, tests.

- [ ] Tests:
  - Review Focus 2;
  - the examples in the EMVCo specification parse field by field;
  - the builder reproduces them byte-exact;
  - `currency()` for `986` is BRL;
  - `amount()` keeps `10.50`;
  - `masked()` hides the merchant-account values and keeps the merchant name;
  - a payload longer than the spec's maximum is refused.
- [ ] Gates; commit `feat: EMVCo merchant-presented QR`.

### Task 6: National EMVCo profiles

**Files:** `src/PaymentQr/{Brazil/PixBrCode,Thailand/PromptPayQr,Singapore/Sgqr,Malaysia/DuitNowQr,Indonesia/Qris,…}.php`
(one per reachable profile), `src/PaymentQr/PaymentQrFactory.php`, tests.

- [ ] Tests:
  - per profile, the official example from its spec, and a payload missing a mandatory field of that spec refused;
  - the factory on each profile, on a generic EMVCo payload, and on an SGQR carrying PayNow;
  - each profile's `masked()` hides its account or proxy value (Pix key, PromptPay phone or ID).
- [ ] Gates; commit `feat: national EMVCo QR profiles`.

### Task 7: EPC, Swiss, Russian and UPI payloads

**Files:**
- `src/PaymentQr/Epc/EpcQr.php`, `src/PaymentQr/Swiss/QrBill.php`;
- `src/Reference/{QrReference,CreditorReference}.php`;
- `src/PaymentQr/Ru/{Gost56042Payload,SbpPaymentLink}.php`, `src/PaymentQr/India/UpiPaymentUri.php`;
- tests.

- [ ] Tests:
  - Review Focus 3;
  - the EPC and SIX examples;
  - a QR-bill with a QR-IBAN and `SCOR` is refused, and one with an ordinary IBAN and `QRR` is refused, as the IG
    states;
  - GOST examples from the standard's annex, and `fromBytes()` on a windows-1251 payload gives the `ST00012` form;
  - an SBP link and a UPI URI from their publications;
  - `masked()` on each hides the IBAN, account or VPA.
- [ ] Gates; commit `feat: EPC, Swiss QR-bill, GOST R 56042, SBP and UPI payloads`.

### Task 8: Doctrine types

- [ ] In `vo-finance-doctrine`:
  - one ORM and one ODM type per value object (`fin_<snake_name>`), with samples and registrar entries;
  - a completeness test;
  - ORM and ODM round trips of a Swiss QR-bill with CRLF and of an EMVCo payload.

  Gates; commit `feat: Doctrine types for vo-finance`.

### Task 9: Documentation

- [ ] For both repositories:
  - `README.md`: the class tables, the "format only" and "profiles not built" lists with reasons, and the IIN-table
    update policy;
  - `CHANGELOG.md` `[Unreleased]`;
  - `.claude/docs/*` and `CLAUDE.md`.

  Commit `docs: vo-finance`.

### Task 10: Review and release 0.1.0

The controller runs this task itself.

- [ ] Inspections and review:
  - PhpStorm `get_inspections` on every changed file, if the MCP is reachable; otherwise say so;
  - `/acc:code-review high` on both packages and on the Task 1 changes;
  - fix every finding; gates; push.
- [ ] Release, when the owner says so: `vo-base` and `vo-base-doctrine` 1.3.0, then `vo-finance` and
  `vo-finance-doctrine` 0.1.0. For each: changelog date, green CI, the owner's confirmation, tag.
