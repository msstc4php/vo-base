# Organisation identifiers: value objects for company, tax and business registration numbers

Date: 2026-11-27 (UTC). Status: draft for the owner's review. Research: one pass over official sources (findings kept
with the plan); the owner asked for a ready solution ("поисследуй все сам и дай мне готовое решение").

## Why

The family covers persons (`vo-documents`), products (`vo-gs1`), publications/transport/telecom (`vo-identifiers`) and
securities/cards/payment QR (`vo-finance`), but not **organisations**. Partner payouts, invoicing and KYB keep handling
a counterparty's VAT number, company registration number or tax number; each project writes a regex for them and rarely
the checksum. A developer should type a property as `Cnpj`, `Abn` or `VatNumber` and get validation, decoded parts, a
way to build the value from parts, and every existing bridge (validation, request input, route, storage) for free.

## Decision: a new package `msstc4php/vo-organisations`

Namespace `MSSTC4PHP\ValueObject\Organisations\`, PHP 8.4, `msstc4php/vo-base ^1.4`, plus
`msstc4php/vo-organisations-doctrine` (ORM + ODM types). *(Designer's call.)*

Runner-up `vo-banking` (domestic bank routing and account numbers) lost: the ABA 3-7-1 rule and routing directory are
licensed (ABA/LexisNexis); UK modulus checking needs Vocalink tables that change several times a year with no stated
licence (a bundled copy would start refusing valid accounts); BSB, Zengin and CNAPS directories are licensed or not
public; no official text was found for the CLABE or NZ check digits. Its one strong official piece, the Bank of Russia
account key, already exists in `vo-finance` (`Internal\RussianAccountKey`) and can be made public there in a minor.

## Rules (the family's, unchanged)

- Checksums and structural rules only from official sources (regulator, tax authority, statistics office, standards
  body, or an official archive copy). Otherwise format only.
- Never refuse a still-valid value; a check found later becomes a report-only `hasValid…(): bool` in a minor.
- `__toString()` / `jsonSerialize()` give the full canonical value (unformatted digits/characters); `formatted()` gives
  the official display form where one is published.
- Exception messages carry no part of the input. Factories never pick silently. Licensed tables are not embedded.
- No online lookups (VIES, ABR, Receita): that is an adapter's job, outside the package.
- Organisation numbers are public registers' data, so no `masked()` *(designer's call)*, except where a number also
  identifies a natural person (sole traders): `RU\Ogrnip` and `FR\Siren` of an individual entrepreneur are still
  public register data; no masking.

## Catalogue

E = check enforced (official algorithm read), R = report-only `hasValidCheckDigit()`, F = format only.

| Class | Identifier | Check | Official source |
|---|---|---|---|
| `AU\Abn` | Australian Business Number, 11 digits | E (mod 89, weights 10 1 3 5 7 9 11 13 15 17 19) | ABR "ABN format" |
| `AU\Acn` | Australian Company Number, 9 digits | E if ASIC's text is read at implementation, else F + R | ASIC |
| `BR\Cnpj` | CNPJ, numeric and the alphanumeric form issued since July 2026 | E (each character as ASCII − 48, mod 11) | Receita Federal FAQ CNPJ alfanumérico (worked example `12.ABC.345/01DE-35`); numeric CNPJs stay valid |
| `CH\Uid` | Swiss UID `CHE-123.456.789` | E (mod 11, weights 5 4 3 2 7 6 5 4; check 10 never issued) | UID-Verordnung Art. 5; eCH-0097 §5.2 |
| `CN\UnifiedSocialCreditCode` | 统一社会信用代码, 18 characters | E if GB 32100-2015 text is read, else F | GB 32100-2015 |
| `EU\VatNumber` | VAT identification number of the 27 member states (+ XI) | F per country (VIES table); IT check shared with `PartitaIva`; others R only where an official text is read | European Commission VIES format table |
| `FR\Siren`, `FR\Siret` | SIREN 9 / SIRET 14 digits | R (Luhn; La Poste SIRETs are a documented exception, so never enforced) | INSEE (to re-read at implementation) |
| `GB\CompanyNumber` | Companies House number, 8 characters with prefixes (SC, NI, OC…) | F | Companies House |
| `GB\VatNumber` | UK VAT 9 / 12 digits, GD/HA | F (HMRC publishes no algorithm) | HMRC |
| `IN\Gstin` | GSTIN, 15 characters (state code, PAN, entity, `Z`, check) | F (no official checksum text found); `pan()` and `stateCode()` decoders | GST Council / CBIC |
| `IT\PartitaIva` | Partita IVA, 11 digits | E (Luhn-variant per DM 23/12/1976 art. 8-9, already in the family's sources) | DM 23 dicembre 1976 |
| `JP\CorporateNumber` | 法人番号, 13 digits | E (9 − (Σ Pn×Qn mod 9)) | 財務省令第70号 第二条; NTA 「法人番号の構成」 |
| `MX\LegalEntityRfc` | RFC of a persona moral, 12 characters | F (SAT `t_RFC_PM`; no homoclave algorithm published) | SAT |
| `PL\Nip`, `PL\Regon` | NIP 10, REGON 9/14 digits | E if the ISAP texts are read, else R | ISAP (Dz.U.) |
| `RU\Ogrn` | ОГРН, 13 digits | E (mod 11, last digit of the remainder) | ПП РФ 438 Прил. 1; Минфин 165н |
| `RU\Ogrnip` | ОГРНИП, 15 digits | E if 165н is read, else R | Минфин 165н |
| `RU\LegalEntityInn` | ИНН юрлица, 10 digits | F (matches vo-documents `RU\Inn`: no official algorithm published) | ФНС |
| `RU\Kpp` | КПП, 9 characters | F; `taxOfficeCode()`, `reasonCode()` decoders | ФНС |
| `US\Ein` | Employer Identification Number, 9 digits | F; report-only `isAssignedPrefix()` against the IRS prefix table (never refuses: prefixes change) | IRS |

Overlaps settled: LEI stays in `vo-finance`; persons' numbers (RU `Inn`, CPF, PAN, MX RFC of a person) stay in
`vo-documents`; a 12-digit Russian value is a person's ИНН and is pointed there by the RU factory's message.

**Left out**, with reasons: DUNS (proprietary); DE USt-IdNr, UK VAT, KR business number, ES CIF, BE/NL mod 97 and the
FR TVA key checks (no official algorithm text found — formats are still covered by `EU\VatNumber` / `GB\VatNumber`);
SG UEN, CA BN, DE Handelsregister number and EORI (no stable official structure found; candidates for a minor once a
source is read); online validation; registry data dumps (their own licences).

## Factories

No global factory: a bare 9-digit value fits SIREN, ACN, EIN, REGON, UK VAT, КПП and the UID digits at once.

- `EU\VatNumber::fromString()` decides by the country prefix and refuses a value without one (a bare number is
  ambiguous by nature).
- `RU\RegistrationNumberFactory`: by length with no overlap — 13 ОГРН, 15 ОГРНИП, 10 ИНН ЮЛ, 9 КПП; 12 refused with a
  message pointing to vo-documents `RU\Inn`.
- `AU\BusinessIdentifierFactory`: 11 → ABN, 9 → ACN.

Candidate sets are frozen per major, as in the other domain packages.

## Doctrine bridge

`vo-organisations-doctrine`: one string type per class for ORM and ODM (`org_<cc>_<snake_name>`), column length = the
class's public `MAX_LENGTH` (VatNumber 14, Cnpj 14, USCC 18…), canonical unformatted value stored, no codecs. The
Symfony bundle's `domain_types` registration learns the new registrar in a bundle minor.

## Size and order

About 20 public classes and 6 internal helpers (~3k lines of src, 3–4k of tests), the size of vo-identifiers 1.x.
Order: research the open sources (below) → plan → implement (one agent at a time) → review → 0.1.0 → bridge 0.1.0 →
audit → 1.0.0, as for the other domain packages. Optional side item: a `vo-finance` minor making the Russian account
key public as a БИК + account check.

## Open at implementation (decides E vs R/F)

Минфин 165н text for ОГРН/ОГРНИП; GB 32100-2015 text; ASIC's ACN algorithm page; the PL texts on ISAP; the CNPJ
"remainder 0 or 1 gives 0" rule in the Receita text; a fresh read of the live VIES table; INSEE's SIREN/SIRET text;
the NTA `T` + 法人番号 layout of qualified invoice numbers (a possible `JP\QualifiedInvoiceIssuerNumber`).
