# Identifiers and machine-readable codes: value objects for barcodes, QR, cards and industrial IDs

Date: 2026-11-19 (UTC). Status: draft for the owner's review; builder check done, its questions settled.

## Why

Direction 3 of the library, after the base value objects and direction 2 (identity documents and names,
`vo-documents`). Applications keep handling identifiers that have a published structure and often a check digit:
the content of barcodes, DataMatrix and QR codes, payment-card numbers, securities and entity codes, publication
numbers, container, vehicle, postal and SIM numbers. Each project rewrites a regex for them and rarely the checksum.

They behave like every other value object of the library (owner's words): the value either comes from outside or is
built locally, is stored in a database and goes out through an API.

Success: a developer types a property as `Gtin`, `Isin` or a payment-QR payload, gets validation, the structure
decoded into typed parts, a way to build the value from its parts (check digit computed), and it works through every
existing bridge (validation, request, route, storage) without writing anything.

## Scope (owner's decisions)

Everything in the catalogue below is in scope (owner: "все нужно").

A value object models the **data**, never the picture: QR and DataMatrix are carriers, the value object is their
content (parse and build), no image rendering.

Each value object follows the library rules the owner set for `vo-documents`, unchanged:

- Checksum and structural rules only when taken from an official source: the issuing body's own publication or a
  standard cited by number (paid ones included); an aggregator counts only when it quotes such a source. Otherwise the
  class checks the format only.
- Never refuse a value that is still valid (old forms still in circulation, e.g. ISBN-10 next to ISBN-13).
- `__toString()` and `jsonSerialize()` give the full value. **This holds for payment-card numbers too** (owner's
  decision, 2026-11-19): encryption at rest and masking are the application's job, as with every other value object.
- `masked()` where the value is sensitive (card numbers, subscriber identities, account data inside payment QR
  codes). *(Designer's call: identifiers that are not personal or secret, e.g. GTIN or ISBN, get no `masked()`.)*
- Exception messages never contain any part of the input.
- A factory never picks a type silently: when a bare value fits several types it throws an "ambiguous" exception
  listing the candidates, as in `vo-documents`.
- Every class cites its source (`@link`); tests use the source's own examples where it has them.
- *(Designer's call, after the builder check)* Paid standards (ISO 6346, ISO/IEC 7812, ISO 3779, ISO/IEC 15459,
  ANSI MH10.8.2, ISBT 128, HIBCC, IFA): the rule is applied as in `vo-documents`. A check is implemented when its text
  is reachable: the standard itself, a free publication of the issuing body, or an aggregator quoting it. Otherwise
  the class checks the format only and the README lists it as format-only with the reason. Nothing waits for a
  purchase.
- *(Designer's call, proposed to the owner 2026-11-19 and not objected to)* Registry-backed codes (UN/LOCODE,
  IATA/ICAO airport codes, MIC, CFI, HS, WMI, GS1 prefixes, card IIN ranges): a value is never refused for missing
  from an embedded list, because a list goes stale and would refuse a validly issued new code. Where the source
  publishes an open table, it is embedded only to decode (scheme name, country of a GS1 prefix). A well-formed
  value outside the table is accepted and decodes as unknown. Proprietary or licensed lists (IATA codes, ICAO Doc
  7910, WCO HS nomenclature, card ranges under NDA) are not embedded. Table updates ship as minor releases.

## Catalogue

### Wave 1: `vo-gs1`

- GS1 identification keys: GTIN-8/12/13/14 (EAN/UPC), GLN, SSCC, GRAI, GIAI, GSRN, GDTI, GCN, GINC, GSIN, CPID, GMN.
  Building from parts (company prefix + reference, check digit computed).
- GS1 element string (Application Identifiers, as carried by GS1-128, GS1 DataMatrix, GS1 QR, DataBar): parse into
  typed AIs and build back; GS1 Digital Link URI; EPC URNs (EPC Tag Data Standard).
- UDI for medical devices from all three FDA-accredited issuing agencies: GS1, HIBCC (HIBC), ICCBBA (ISBT 128); US
  NDC; EU Basic UDI-DI.
- Marking codes: Russian "Честный знак" (structure per product group from the operator's official methodology),
  EU FMD pharmaceutical pack codes (GS1 and IFA/PPN), EU tobacco traceability codes.

### Wave 2: `vo-finance`

- Securities and entities: ISIN, LEI, UPI (ISO 4914), CFI, FISN, MIC, FIGI; CUSIP, SEDOL, WKN, Valor (check digit only
  where an official source publishes it, otherwise format only).
- Payment cards: a new card-number class with full IIN ranges (ISO/IEC 7812), every card scheme, per-scheme lengths,
  masking. **`vo-base\Finance\CreditCardNumber` is marked `@deprecated` pointing to it** (owner's decision); it is
  removed no earlier than the next major `vo-base`. CVV and PIN get no value object (must not be stored under PCI DSS).
- Payment QR payloads: EMVCo Merchant-Presented QR and the national profiles built on it that publish a spec (Pix BR
  Code, UPI QR, PromptPay, SGQR, DuitNow, QRIS and others found at planning), EPC QR (SEPA), Swiss QR-bill,
  GOST R 56042 (RU), SBP (НСПК).

### Wave 3: `vo-identifiers`

- Publications and media: ISBN, ISSN, ISMN, ISRC, ISWC, ISAN, ISNI, ORCID, DOI; ROR, ARK where a spec exists.
- Transport and logistics: VIN and WMI (ISO 3779/3780), container number (ISO 6346), IMO ship number, MMSI, UPU S10
  postal item, IATA air waybill, UIC/EVN rail vehicle number, UN/LOCODE, ICAO/IATA airport codes, ISO/IEC 15459
  unique identifiers and ANSI MH10.8.2 data identifiers.
- Telecom: IMEI/IMEISV, ICCID, IMSI, EID.
- Chemistry, trade, energy: CAS registry number, EC number, UN dangerous-goods number, HS/customs tariff codes, EIC
  (ENTSO-E), MPAN (UK).
- ICAO 9303 MRZ parsing (TD1, TD2, TD3), which direction 2 deferred here. It goes into `vo-documents` (owner's
  decision, 2026-11-19), as a minor release of `vo-documents` and its bridge in this wave, because it yields
  `Icao\PersonName` and document numbers that live there.

### Serial numbers

No general standard exists. *(Designer's call: a serial number is a typed part of the structures that carry it, AI
(21), data identifier `S`, the serial inside VIN, IMEI and UDI, not a free-standing value object.)*

## Relation to `vo-base` (checked 2026-11-19)

Not duplicated, reused: `Finance\Iban`, `Finance\Bic`, `Finance\Decimal`, `Format\PhoneNumber` (E.164),
`Identity\Uuid`, `I18n\CountryCode`, `I18n\LanguageCode`, `Network\Uri`/`Url` (GS1 Digital Link, DOI, URNs build on
them). ULID is left out: no official standard.

Gaps filled in `vo-base` itself, as a minor release before wave 1 *(designer's call)*:

- `I18n\CurrencyCode::numeric()` / `fromNumeric()` (EMVCo QR carries the ISO 4217 numeric code).
- EUI-64 and OUI next to `Network\MacAddress`.
- Per-country IBAN structure from the SWIFT IBAN registry is a possible improvement of `Finance\Iban`, decided at
  planning.

- *(Designer's call, after the builder check)* The shared contracts that the new packages need move into `vo-base`:
  the `Maskable` interface and a generic "ambiguous value" exception carrying the candidate classes. `vo-documents`
  keeps its own names as subtypes, so nothing breaks there.

The `CreditCardNumber` deprecation ships in the `vo-base` release that accompanies wave 2.

## Integration

- Three new packages, each with its own Doctrine bridge, same scaffolding and gates as `vo-documents` and
  `vo-documents-doctrine`: `msstc4php/vo-gs1`, `msstc4php/vo-finance`, `msstc4php/vo-identifiers` and their
  `-doctrine` bridges (owner's decision). The owner creates the GitHub repositories.
- *(Designer's call)* Namespaces `MSSTC4PHP\ValueObject\Gs1\`, `MSSTC4PHP\ValueObject\Financial\` (not `Finance\`,
  which `vo-base` already uses) and `MSSTC4PHP\ValueObject\Identifiers\`.
- `ValueObjectMapper` recognises any `StringConvertible`, so every value object works in all bridges with no core
  release. *(Designer's call: composite values, such as an element string or a payment QR payload, are still string
  value objects whose canonical string is the encoded content, so they need no payload codec.)*
- A package may depend on an earlier wave's package where a structure embeds it (e.g. `vo-finance` payment QR on
  nothing new; `vo-identifiers` may use `vo-gs1` for ISBN-as-GTIN).

## Goals

- Each wave released as `0.1.0` of its package and bridge; later additions as minor releases.
- Every checksum tested against the official examples.
- Same gates as every package: PHPStan max + `phpstan-ci.neon`, cs-fixer, rector, tests, Infection >= 75% covered
  MSI, lowest-deps job, templates `cmp`, `/acc:code-review`, `README`, `CHANGELOG`, `.claude/docs`.

## Non-goals

- Rendering or decoding images (barcode, QR, DataMatrix pictures); only their content.
- Checking a value against a registry (whether a GTIN, LEI or IMEI is actually assigned); only its form, with
  published static ranges (IIN, GS1 prefixes) where the source publishes them.
- Storing CVV or PIN.
- Packagist publication (the owner does it in the web UI).

## Anti-goals (failures even if it "works")

- An exception message containing the input value.
- A checksum or structure taken from a blog or generator site instead of the official source.
- Refusing a value that is still valid in circulation.
- A factory that silently picks one of several matching types.
- A canonical string that does not round-trip losslessly (e.g. an element string whose separators are lost).

## Left to the builder

Class names, file layout, the exact list per group within the rule above, which national payment-QR profiles have a
published spec, the canonical string form of composite values (must round-trip), masking rules per class, test
structure, message wording within the anti-goals, the order inside a wave.

## Release

Before wave 1: `vo-base` minor. After each wave: its package and bridge `0.1.0`. Tag only after green CI.

*(Designer's call, after the builder check)* A wave is released when its whole catalogue is in. A class whose source
is unreachable ships format-only rather than being moved to a later release. Each wave gets its own implementation
plan or plans, written when the wave starts, as with the regional plans of `vo-documents`.
