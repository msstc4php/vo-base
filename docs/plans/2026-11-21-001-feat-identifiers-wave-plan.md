# Direction 3, plan 3: wave 3, `vo-identifiers` and `vo-identifiers-doctrine` 0.1.0, and ICAO 9303 MRZ in `vo-documents` 0.2.0

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** The identifiers package and its Doctrine bridge, released as 0.1.0. The package covers:
- publications and media;
- transport and logistics;
- telecom;
- chemistry, trade and energy.

Then the ICAO 9303 machine-readable zone (TD1, TD2, TD3) in `vo-documents` and its bridge, released as 0.2.0.

**Architecture:** Every value object is a string value object (`AbstractStringValueObject`), the MRZ included. The
MRZ's canonical string is its lines joined by `\n`, so `ValueObjectMapper` and every bridge handle it with no codec.
Check-digit algorithms live in `Internal\` helpers of each package. `vo-identifiers` depends on `vo-gs1` for the
GTIN form of ISBN and ISMN.

**Tech Stack:** PHP 8.4, `msstc4php/vo-base ^1.2`, `msstc4php/vo-gs1 ^0.1`, PHPUnit, PHPStan max, Infection.

**Spec:** `vo-base/docs/brainstorms/2026-11-19-identifiers-and-codes-requirements.md`. Where this plan says nothing,
the conventions fixed by plans 1 and 2 (`2026-11-19-001-feat-gs1-wave-plan.md`, `2026-11-20-001-feat-finance-wave-plan.md`)
and by `vo-gs1` and `vo-finance` as built apply.

## Global Constraints

- **Sources.** A check digit or a structural rule is enforced only when it is quoted from an official source:
  - the issuing body's or registration authority's own publication;
  - a regulator's act or an intergovernmental body's publication (ICAO, IMO, ITU, UPU, UNECE, UN, WCO, ENTSO-E);
  - a standard cited by number, paid ones included;
  - an unmodified Internet Archive copy of any of these.

  An aggregator counts only "as quoted from" such a source. Otherwise the class checks the format only, and the
  README's "format only" list gives the reason.
- **Validity.** Never refuse a value that is still valid: old forms in circulation are accepted (ISBN-10 next to
  ISBN-13, IMEI with or without its check digit, 7-digit ISSN typed without the check).
- **Registries.** Registry-backed data is used only to decode, never to refuse: WMI, MMSI MID, UN/LOCODE, airport
  codes, HS chapters, ICCID issuer IDs. A well-formed value outside the table decodes as unknown. Proprietary or
  licensed lists (IATA codes, ICAO Doc 7910, WCO HS nomenclature) are not embedded.
- **Output and masking.**
  - `__toString()` and `jsonSerialize()` give the full value.
  - vo-base `Contract\Maskable` (`masked()`) is implemented by the subscriber identities (`Imsi`, `Iccid`, `Eid`,
    `Imei`) and by the MRZ. Publication, transport, chemistry and energy identifiers get no `masked()`; `Mpan`
    neither (it identifies a meter point, not a person).
- **Exception messages** never contain the input or any part of it, not even its length. Never call
  `InvalidValueObjectException::excerpt()` in the new code.
- **Factories** never pick silently. A value that fits several types throws vo-base `Exception\AmbiguousValueException`
  with the candidates.
- **Citations.** Every class cites its source with `@see` (the template cs-fixer rewrites `@link`). Tests use the
  source's own examples where it has them.
- **New packages.**
  - `msstc4php/vo-identifiers`: namespace `MSSTC4PHP\ValueObject\Identifiers\`; `require`: `php >=8.4`,
    `msstc4php/vo-base ^1.2`, `msstc4php/vo-gs1 ^0.1`.
  - `msstc4php/vo-identifiers-doctrine`: namespace `MSSTC4PHP\ValueObject\Identifiers\Doctrine\`; `require`:
    `msstc4php/vo-identifiers ^0.1`, `msstc4php/vo-base-doctrine ^1.2`.
  - Scaffold, CI, `composer-ci.json`, `.claude/docs` and `CLAUDE.md`: copied from `vo-finance` /
    `vo-finance-doctrine`.
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

- **Doctrine type names:** `id_<snake_name>` (`id_isbn`, `id_container_number`, `id_imei`). The column length is the
  class's maximum length. In `vo-documents-doctrine` the MRZ type is `vod_icao_mrz` (its existing `vod_` prefix), length 92
  (three lines of 30 plus two separators).
- **Exceptions** in `Identifiers\Exception\`, all extending vo-base `InvalidValueObjectException`:
  `InvalidPublicationIdentifierException`, `InvalidTransportIdentifierException`, `InvalidTelecomIdentifierException`,
  `InvalidSubstanceIdentifierException`, `InvalidEnergyIdentifierException`. MRZ: `vo-documents`
  `Exception\InvalidMrzException`.
- **Canonical forms.** Each class strips only the separators its source prints (hyphens and spaces in ISBN, ISSN,
  ISMN, ISAN, ISNI, IMEI; spaces in container and S10 numbers) and stores the compact uppercase form. A
  `formatted()` accessor gives the printed form where the source defines one (`978-…` needs the ISBN range table,
  see below). Identifiers whose case or punctuation is significant (DOI suffix, ARK, URLs) keep the value as given,
  apart from the case-folding the source allows.
- **Publications and media** (`Identifiers\Publication\`):
  - `Isbn` (ISO 2108, International ISBN Agency User's Manual): ISBN-13 (EAN check digit, prefix 978 or 979) and
    ISBN-10 (mod 11, `X`). Both forms are accepted; the canonical string keeps the form given.
    - `isbn13(): string`, `isbn10(): ?string` (null for 979), `toGtin(): Gtin` (vo-gs1), `isIsbn10(): bool`.
    - `formatted(): ?string` with hyphens from the International ISBN Agency's RangeMessage, if its terms allow
      embedding the ranges; otherwise the method is not built and the README says why. A value outside the table is
      accepted, `formatted()` null.
  - `Issn` (ISO 3297): 8 characters, mod 11 with `X`; `NNNN-NNNC` is the printed form, the canonical form keeps the
    hyphen. `toGtin(string $issue = '00'): Gtin` gives the 977 GTIN, as the ISSN Manual defines it.
  - `Ismn` (ISO 10957): `979-0` + 9 digits, EAN check digit; the old `M` form is accepted and converted to
    `979-0` (the ISMN Manual states their equivalence). `toGtin(): Gtin`.
  - `Isrc` (ISO 3901): 12 characters, country/registrant/year/designation; no check digit. Printed with hyphens;
    canonical without. Accessors per part.
  - `Iswc` (ISO 15707): `T` + 9 digits + check digit, the CISAC weighting.
  - `Isan` (ISO 15706): root, episode, version, with the ISO 7064 MOD 37,36 check characters; both the 16-digit
    (no version) and 24-digit forms.
  - `Isni` (ISO 27729): 16 characters, ISO 7064 MOD 11-2. `Orcid` is an ISNI-range identifier with the same check,
    accepted bare or as `https://orcid.org/…`; canonical the bare form, `uri()` the URL.
  - `Doi` (ISO 26324, DOI Handbook): `10.` + registrant + `/` + suffix; case-insensitive for comparison (`equals()`
    folds ASCII case as the Handbook states), stored as given. Accepted bare, `doi:`-prefixed or as
    `https://doi.org/…`; canonical the bare form.
  - `Ror` (ROR ID): `0` + 6 Crockford base32 characters + 2-digit ISO 7064 MOD 97-10 check, bare or as
    `https://ror.org/…`.
  - `Ark` (ARK Alliance spec, draft-kunze-ark): `ark:` + NAAN + `/` + name; format only unless the spec makes a check
    character mandatory (the NOID check is optional, so it is not enforced).
  - `PublicationIdentifierFactory::fromString()`: one class or `AmbiguousValueException` (an ISNI and an ORCID are
    the same value form: ambiguous unless given as an ORCID URL).
- **Transport and logistics** (`Identifiers\Transport\`):
  - `Vin` (ISO 3779): 17 characters, no `I`, `O`, `Q`.
    - The check digit at position 9 is enforced only where it is mandatory: North America (49 CFR 565, WMI
      region 1-5) and wherever the research finds another mandate. Elsewhere position 9 is not checked, because
      ISO 3779 does not require it. `hasValidCheckDigit(): bool` is always available.
    - `wmi(): Wmi`, `vds()`, `vis()`, `modelYear(): ?list<int>` (the year code is ambiguous on a 30-year cycle, so a
      list of candidate years, never one picked silently).
    - Old VINs before 1981 are not 17 characters and are not VINs under ISO 3779: out of scope, the README says so.
  - `Wmi` (ISO 3780): 3 characters; `region()` and `country()` decode from the published SAE/ISO country table, null
    when unknown. Manufacturer names are not embedded (licensed list).
  - `ContainerNumber` (ISO 6346): owner code (3 letters), category (`U`, `J`, `Z`), 6 digits, check digit with the
    letter values that skip multiples of 11. Accepts spaces; canonical compact. The check digit `10` is written `0`
    per the standard; the research confirms the BIC's wording.
  - `ImoNumber` (IMO Res. A.1078(28) for ships, A.1117(30) for companies and registered owners): `IMO` + 7 digits,
    weighted check digit for the ship number. Company numbers have the same form; `ImoCompanyNumber` is built only if
    the research finds its check; otherwise the ship class alone.
  - `Mmsi` (ITU-R M.585): 9 digits; `type(): MmsiType` (ship, group, coast station, SAR aircraft, AIS AtoN, craft
    associated, handheld, MOB/EPIRB-AIS/SART) from the leading digits; `mid(): ?string` and `country(): ?CountryCode`
    from the ITU MID table, if its terms allow; otherwise only the MID digits.
  - `S10ItemIdentifier` (UPU S10): 2 letters service indicator, 8-digit serial, check digit (weights 8 6 4 2 3 5 9 7,
    mod 11 rules), 2-letter UPU country code. `serviceIndicator()`, `country()`.
  - `AirWaybillNumber` (IATA Resolution 600a / Cargo-IMP): 3-digit airline prefix + 8 digits, the last of which is
    the serial mod 7. If IATA's text is unreachable, format only.
  - `UicVehicleNumber` (UIC 438 / OTIF uniform technical prescriptions, EU Decision 2007/756/EC Annex P and its
    successor): 12 digits, Luhn-style check digit (weights 2 1 alternating); printed form with spaces and hyphen.
    `vehicleType()`, `countryCode()` (UIC numeric country code, raw digits).
  - `UnLocode` (UNECE Recommendation 16): ISO 3166 alpha-2 + 3 characters (letters and digits 2-9); format only.
    `country(): ?CountryCode`. The UN/LOCODE list is not embedded (changes twice a year).
  - `IataAirportCode` (3 letters) and `IcaoAirportCode` (4 letters, `region()` from the first letter per ICAO
    Doc 7910's published nationality letters, if reachable): format only, no list (licensed).
  - `Iso15459Identifier` (ISO/IEC 15459-1..-6): issuing agency code (IAC) + company ID + serial; parsed when it is
    carried with an MH10.8.2 data identifier (`25S`, `J`, `25B`, …). `issuingAgencyCode()` decodes from the AIM
    (registration authority) IAC list if it is public.
  - `DataIdentifierString` (ANSI MH10.8.2): a sequence of data-identifier fields as carried in ISO/IEC 15434
    format `06`; parse into typed `DataIdentifierField` (`identifier()`, `value()`) and build back. The canonical
    string is the format-06 envelope with its separators (`RS`, `GS`, `EOT`) exactly as given (lossless). The DI
    table is embedded only if its terms allow; otherwise any `\d{0,3}[A-Z]` identifier parses with a null
    description. The serial number DI `S` is a typed accessor `serial()`.
  - `TransportIdentifierFactory::fromString()`: one class or `AmbiguousValueException`.
- **Telecom** (`Identifiers\Telecom\`):
  - `Imei` (3GPP TS 23.003 §6.2): 14 digits + Luhn check digit (15), or 14 digits alone (the check digit is not
    transmitted over the air, so a 14-digit IMEI is valid). `tac()`, `serialNumber()`, `checkDigit(): string`
    (computed when absent), `withCheckDigit(): self`.
  - `Imeisv` (3GPP TS 23.003 §6.2.2): 14 digits + 2-digit software version, no check digit. `toImei(): Imei`.
  - `Iccid` (ITU-T E.118): `89` + country code + issuer + account + Luhn check, 18-20 digits (19 or 20 with the
    check; the research confirms whether 18-digit no-check forms are in circulation). `issuerIdentifier()` raw.
  - `Imsi` (3GPP TS 23.003 §2.2, ITU-T E.212): up to 15 digits: MCC (3) + MNC (2 or 3) + MSIN. The MNC length is
    not in the value: `mcc()`, and `mnc(int $length)` / `msin(int $length)` take it from the caller. No embedded
    MNC table (it is published by ITU but changes; the MCC country table is embedded if the ITU terms allow).
  - `Eid` (GSMA SGP.02 / SGP.29): 32 digits, ISO 7064 MOD 97-10 check digits at positions 31-32, prefix `89`.
  - `masked()` for all four keeps the leading network or device part (IMEI: the 8-digit TAC; ICCID: `89` + country
    code + issuer; IMSI: the first 5 digits, the shortest MCC + MNC; EID: the first 8 digits) and the last 2 digits.
  - `TelecomIdentifierFactory::fromString()`: one class or `AmbiguousValueException` (15 digits can be an IMEI and an
    IMSI; 19 digits starting `89` is an ICCID).
- **Chemistry, trade, energy** (`Identifiers\Substance\`, `Identifiers\Trade\`, `Identifiers\Energy\`):
  - `CasRegistryNumber` (CAS): 2-7 digits, 2 digits, check digit (weighted mod 10), printed with hyphens and canonical
    with them.
  - `EcNumber` (ECHA): `NNN-NNN-N`, mod 11 check digit, as ECHA publishes it.
  - `UnNumber` (UN Model Regulations, Orange Book): `UN` + 4 digits; accepted with or without `UN`, canonical with
    it. Format only (no check exists); no embedded substance list.
  - `HsCode` (WCO Harmonized System): 6 digits (chapter, heading, subheading), and `TariffCode` for the national
    extensions (8 or 10 digits: EU CN 8, TARIC 10, US HTS 10). Format only, `chapter()`, `heading()`,
    `subheading()`. No nomenclature embedded (WCO licence).
  - `EnergyIdentificationCode` (ENTSO-E EIC Reference Manual): 16 characters, 2-character issuing office, object-type
    letter, 12 characters, check character with ENTSO-E's algorithm. `issuingOffice()`, `objectType(): EicObjectType`.
  - `Mpan` (UK MRA, DCUSA Schedule): the 13-digit core (distributor ID 2, unique ID 8, check digit 1 with the
    published prime weights) and the full 21-digit form with the top line (profile class, MTC, LLFC). Both accepted;
    `core()`, `distributorId()`, `profileClass(): ?string`.
- **MRZ** (`vo-documents`, `Documents\Icao\Mrz\`), per ICAO Doc 9303 Parts 3, 4, 5, 6:
  - `MachineReadableZone implements Maskable, HasSex`: abstract over the three formats; `fromString()`
    accepts 2 lines of 44 (TD3), 2 lines of 36 (TD2) or 3 lines of 30 (TD1), joined by `\n`, `\r\n` or given as a
    single string of exactly 88, 72 or 90 characters. Canonical: lines joined by `\n`. The concrete classes `Td1`,
    `Td2`, `Td3` are final.
  - Every check digit of the format (7-3-1 weights, `<` = 0, letters A=10…Z=35) is enforced, the composite one
    included. TD1 long document numbers (Part 5 §4.2.4: overflow into the optional data, `<` at check position) are
    supported.
  - Accessors: `documentCode()`, `issuingState()` and `nationality()` (raw 3-character codes, since Part 3 adds
    codes outside ISO 3166, e.g. `D`, `GBD`, `UNO`, `XXA`), `issuingCountry(): ?CountryCode` and
    `nationalityCountry(): ?CountryCode` where the code is an ISO 3166 alpha-3, `documentNumber()`, `name():
    PersonName` (vo-documents `Icao\PersonName`, the MRZ name field decoded), `sex(): ?Sex` (`<` is null),
    `optionalData()`, `birthDateDigits()` and `expiryDateDigits()` (raw `YYMMDD`).
  - Dates: the century is not in the MRZ. `expiryDate(): DateTimeImmutable` uses Part 3's rule if it gives one;
    `birthDate(DateTimeImmutable $today)` picks the most recent past century relative to the given date, never the
    clock (the caller passes the clock; no hidden `now`). An unknown day or month (`<<` per Part 3) makes the date
    null, `birthDateDigits()` still returns it. So `MachineReadableZone` does not implement `HasBirthDate` (whose
    method takes no argument); the CHANGELOG says so.
  - `masked()`: the document number, the optional data and the birth date replaced by `*`. `*` is not an MRZ
    character, so a masked MRZ never parses and never passes for a real one. The name, sex, states and expiry stay
    visible.
  - `MrzFactory` is not needed: the format follows from the length, never ambiguous.
  - Visa formats MRV-A/MRV-B (Part 7) are out of this plan; they share the code, so they are a later minor.

## Review Focus

1. Check digits against the source's own examples, and one value with a wrong check per class, refused with a
   message naming only the type: ISBN-10 `0-306-40615-2`, ISBN-13 `978-0-306-40615-7`, ISSN `0378-5955`,
   container `CSQU3054383`, IMO `IMO 9074729`, CAS `7732-18-5`, IMEI `490154203237518`, the ICAO 9303 Part 4
   specimen passport.
2. Old and alternative forms still in circulation are accepted: ISBN-10 next to ISBN-13; a 14-digit IMEI; an ISMN in
   the `M` form; an ORCID as a URL and bare; an IMEI with spaces; a container number with spaces; a lowercase VIN is
   uppercased.
3. Ambiguity: 15 digits valid as both an IMEI and an IMSI, an ISNI/ORCID value, an IATA code that is also a valid
   UN/LOCODE location part: each factory throws `AmbiguousValueException` with the candidates, never a silent pick.
4. VIN outside North America with a "wrong" position 9 is accepted (`hasValidCheckDigit()` false); inside North
   America it is refused. `modelYear()` returns two candidates 30 years apart.
5. MRZ:
   - TD1, TD2 and TD3 specimens from Doc 9303 parse field by field and round-trip byte-exact;
   - CRLF input gives the `\n` canonical form;
   - a wrong composite check digit is refused;
   - a TD1 long document number;
   - a birth date with `<<` for the day;
   - no character of the input in any message, `masked()` not parseable.

---

### Task 1: Scaffold, sources, check-digit helpers

**Files:**
- `vo-identifiers/` and `vo-identifiers-doctrine/` scaffold (from `vo-finance` / `vo-finance-doctrine`);
- `vo-identifiers/docs/sources/IDENTIFIERS.md`;
- `src/Internal/{Luhn,Ean13CheckDigit,Mod11CheckDigit,Iso7064Mod11_2,Iso7064Mod37_36,Iso7064Mod97,WeightedCheckDigit,Iso6346CheckDigit,VinCheckDigit,S10CheckDigit,CasCheckDigit,EicCheckDigit,MpanCheckDigit}.php`
  (names may merge where two sources use one algorithm);
- tests.

**Interfaces:** Each helper has `compute(string $body): string` and `isValid(string $value): bool`.

- [ ] Research into `IDENTIFIERS.md`, with a quote and a link per rule, covering every source this plan names:
  ISBN (International ISBN Agency Manual and RangeMessage terms), ISSN Manual, ISMN Manual, ISRC Handbook, CISAC
  ISWC, ISAN, ISNI and ORCID, DOI Handbook, ROR, ARK; ISO 3779/3780 and 49 CFR 565 (and any other position-9
  mandate), ISO 6346 / BIC, IMO resolutions, ITU-R M.585 and the MID table terms, UPU S10, IATA AWB, UIC/EVN, UNECE
  Rec. 16, ICAO airport codes, ISO/IEC 15459 and the IAC list, ANSI MH10.8.2 and ISO/IEC 15434; 3GPP TS 23.003,
  ITU-T E.118 and E.212, GSMA SGP.29; CAS, ECHA EC number, UN Model Regulations, WCO HS, ENTSO-E EIC, UK MPAN.
  Record which sources are unreachable; those classes become format only.
- [ ] Ask the owner to create `msstc4php/vo-identifiers` and `msstc4php/vo-identifiers-doctrine` (they do not exist
  on 2026-11-21); work on local `main` meanwhile.
- [ ] Tests: each helper with its source's own example (the Review Focus 1 values, and the ISO 7064 examples of
  ISNI and ROR).
- [ ] Gates; commit `feat: scaffold, identifier sources and check digits`; push when the repo exists.

### Task 2: Publications and media

**Files:** `src/Publication/{Isbn,Issn,Ismn,Isrc,Iswc,Isan,Isni,Orcid,Doi,Ror,Ark,PublicationIdentifierFactory}.php`,
`src/Internal/IsbnRanges.php` (generated by `tools/generate-isbn-ranges.php` if the terms allow), tests.

- [ ] Tests: Review Focus 1-3 for these classes; `Isbn` 10 → 13 and 13 → GTIN; 979 ISBN has no ISBN-10; ISSN →
  977 GTIN; DOI equality ignores ASCII case; a DOI with a `/` and Unicode in the suffix round-trips.
- [ ] Gates; commit `feat: publication and media identifiers`.

### Task 3: Transport and logistics

**Files:** `src/Transport/{Vin,Wmi,ContainerNumber,ImoNumber,Mmsi,MmsiType,S10ItemIdentifier,AirWaybillNumber,UicVehicleNumber,UnLocode,IataAirportCode,IcaoAirportCode,Iso15459Identifier,DataIdentifierString,DataIdentifierField,TransportIdentifierFactory}.php`,
tests.

- [ ] Tests: Review Focus 1-4 for these classes; an MH10.8.2 format-06 envelope with `25S`, `S` and `1P` parses and
  round-trips byte-exact; an S10 item from the UPU standard's example; a UIC number in printed and compact form.
- [ ] Gates; commit `feat: transport and logistics identifiers`.

### Task 4: Telecom

**Files:** `src/Telecom/{Imei,Imeisv,Iccid,Imsi,Eid,TelecomIdentifierFactory}.php`, tests.

- [ ] Tests: Review Focus 1-3 for these classes; `Imei` 14 → 15 digits; `Imeisv::toImei()`; EID from the GSMA
  example; `masked()` per class keeps no more than the decided part.
- [ ] Gates; commit `feat: telecom identifiers`.

### Task 5: Chemistry, trade, energy

**Files:** `src/Substance/{CasRegistryNumber,EcNumber,UnNumber}.php`, `src/Trade/{HsCode,TariffCode}.php`,
`src/Energy/{EnergyIdentificationCode,EicObjectType,Mpan}.php`, tests.

- [ ] Tests: the sources' own examples (CAS, ECHA, ENTSO-E, MPAN); `UN1203` and `1203` give the same value; a TARIC
  code decodes its HS subheading.
- [ ] Gates; commit `feat: chemistry, trade and energy identifiers`.

### Task 6: Doctrine types

- [ ] In `vo-identifiers-doctrine`: one ORM and one ODM type per value object (`id_<snake_name>`), with samples and
  registrar entries; a completeness test; ORM and ODM round trips of an MH10.8.2 envelope with control characters.
  Gates; commit `feat: Doctrine types for vo-identifiers`.

### Task 7: ICAO 9303 MRZ in `vo-documents`

**Files:** `vo-documents/src/Icao/Mrz/{MachineReadableZone,Td1,Td2,Td3}.php`, `src/Exception/InvalidMrzException.php`,
`src/Internal/MrzCheckDigit.php`, `docs/sources/ICAO.md` (MRZ section added), tests; `vo-documents-doctrine`: ORM
and ODM `vod_icao_mrz` types, registrar entries, tests.

- [ ] Research, quoted into `ICAO.md`: Doc 9303 Part 3 (check digit, codes outside ISO 3166, dates), Part 4 (TD3),
  Part 5 (TD1, long document numbers), Part 6 (TD2), and their specimens.
- [ ] Tests: Review Focus 5; `name()` of the Part 4 specimen equals `Icao\PersonName::fromParts(...)` of its VIZ
  name; `sex()` `<` is null; Germany's `D<<` gives `issuingState()` `D` and `issuingCountry()` `DEU` only if
  Part 3 maps it, otherwise null (the research decides, the test pins it).
- [ ] Gates in both repos; commit `feat: ICAO 9303 machine-readable zone` and `feat: Doctrine type for the MRZ`;
  push; green CI.

### Task 8: Documentation

- [ ] For `vo-identifiers`, `vo-identifiers-doctrine`, `vo-documents`, `vo-documents-doctrine`:
  - `README.md`: the class tables, the "format only" list with reasons, the registry-table update policy;
  - `CHANGELOG.md` `[Unreleased]`;
  - `.claude/docs/*` and `CLAUDE.md`.

  Commit `docs: vo-identifiers` and `docs: MRZ`.

### Task 9: Review and release

The controller runs this task itself.

- [ ] Inspections and review:
  - PhpStorm `get_inspections` on every changed file, if the MCP is reachable; otherwise say so;
  - `/acc:code-review high` on both new packages and on the `vo-documents` changes;
  - fix every finding; gates; push.
- [ ] Release, when the owner says so: `vo-identifiers` and `vo-identifiers-doctrine` 0.1.0; `vo-documents` and
  `vo-documents-doctrine` 0.2.0. For each: changelog date, green CI, the owner's confirmation, tag.
