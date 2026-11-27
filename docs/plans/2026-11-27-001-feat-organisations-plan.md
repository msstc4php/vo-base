# Organisations plan 1: `vo-organisations` and `vo-organisations-doctrine` 0.1.0

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Two new local repositories next to the siblings, `msstc4php/vo-organisations` 0.1.0 (company, tax and
business registration numbers of AU, BR, CH, CN, the EU VAT area, FR, GB, IN, IT, JP, MX, PL, RU, US) and
`msstc4php/vo-organisations-doctrine` 0.1.0 (ORM and ODM types), pushed and tagged once the owner has created the
GitHub repositories.

**Architecture:** Every value object is a string value object (`AbstractStringValueObject`) whose canonical string is
the unformatted identifier, so `ValueObjectMapper` and every bridge handle it with no codec. Check algorithms live in
small `Internal\` classes, one per source, built on two shared primitives (a weighted sum over ASCII − 48 and a
digit-by-digit remainder). Whether a class enforces its check (E), reports it (R) or checks the format only (F) is
decided once, in Task 0, from the official text; later tasks implement the branch Task 0 recorded.

**Tech Stack:** PHP 8.4, `msstc4php/vo-base ^1.4`, `msstc4php/vo-base-doctrine ^1.3`, PHPUnit, PHPStan max, Infection.

**Spec:** `vo-base/docs/brainstorms/2026-11-27-organisations-requirements.md`; research with sources and quotes:
`step9-research.md` (session scratchpad; Task 0 moves what it needs into `docs/sources/ORGANISATIONS.md`). Where this
plan says nothing, the conventions of `vo-finance` / `vo-finance-doctrine` and `vo-identifiers` as built apply.

## Global Constraints

The family's rules, verbatim from the spec:

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
- Candidate sets are frozen per major, as in the other domain packages.

Packages and tooling:

- `msstc4php/vo-organisations`: namespace `MSSTC4PHP\ValueObject\Organisations\`; `require`: `php >=8.4`,
  `msstc4php/vo-base ^1.4`. No dependency on vo-documents or vo-finance; no `ext-bcmath`.
- `msstc4php/vo-organisations-doctrine`: namespace `MSSTC4PHP\ValueObject\Organisations\Doctrine\`; `require`:
  `php >=8.4`, `msstc4php/vo-organisations ^0.1`, `msstc4php/vo-base-doctrine ^1.3`.
- Tool configs byte-identical to the `php84` templates of `/home/user/PhpstormProjects/msstc4symfony/bundle-standard`
  tag `v4.2.0` (`.php-cs-fixer.dist.php`, `rector.php`, `phpstan-ci.neon`, `phpunit.xml.dist`, `infection.json5`,
  `Makefile`, `.gitignore`; `phpstan.dist.neon` differs only by `level: max`), never edited.
- **Gates** (every task that touches PHP): `COMPOSER=composer-local.json make check`; `vendor/bin/phpunit`;
  `XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text` with no warning, risky or deprecation;
  `XDEBUG_MODE=coverage vendor/bin/infection --threads=8 --no-interaction --min-msi=75 --min-covered-msi=75`
  (never `make infection`; `php -d memory_limit=-1` if the reporter runs out of memory).
- Before every push: refresh the local tools with `COMPOSER=composer-local.json composer update
  --ignore-platform-req=php`, then run the gates again.
- One agent at a time: no parallel subagents, in implementation and in review.
- Commit identity `Maxim Shamaev <maxim.shamaev@gmail.com>`, trailer `Co-Authored-By: Claude Opus 5.5
  <noreply@anthropic.com>`. Commits are made without asking; pushes as soon as the remotes exist; a tag only after
  green CI and the owner's confirmation. The GitHub repositories and Packagist are the owner's.
- Every class cites its source with `@see` (cs-fixer rewrites `@link`). Tests use the source's own examples where it has
  them. Code comments in English, minimal. Strict PHPStan types. Dates in UTC.

## Decisions this plan makes

- **Exception:** one family, `Exception\InvalidOrganisationIdentifierException` (`final`, extends vo-base
  `InvalidValueObjectException`). Messages are `<type> <reason>.`, the reasons in `Internal\Reason`:
  `INVALID_FORMAT` `has an invalid format`, `WRONG_CHECK_DIGIT` `has a wrong check digit`, `WRONG_CHECK_CHARACTER`
  `has a wrong check character`, `NEVER_ISSUED` `has a check digit that is never issued`, `NO_PREFIX` `has no country
  prefix`, `UNKNOWN_PREFIX` `has an unknown country prefix`. No exception to "no part of the input" (controller
  ruling 2026-11-27 UTC): the RU factory gives ONE message for every refusal, so it reveals nothing about the length:
  `Russian registration number fits no class (OGRN 13, OGRNIP 15, legal-entity INN 10, KPP 9 characters); a person's
  INN is msstc4php/vo-documents RU\Inn.`
- **Input:** each class strips ASCII spaces and the separators of its printed form only (listed per class), and
  ASCII-uppercases; anything else is refused. Never zero-padding: a value of the wrong length is refused.
- **E / R branches:** an E class refuses a wrong check and has `fromParts()` computing it; an R class accepts it and has
  `hasValidCheckDigit(): bool` (no `fromParts()`); an F class has neither. Its docblock names the branch and the
  `ORGANISATIONS.md` section.
- **`EU\VatNumber`:** `from()` (the spec's `fromString()`) decides by the VIES prefix: `EL` for Greece and `XI` for
  Northern Ireland are kept; `GR`, `GB` and a missing prefix are refused (`UNKNOWN_PREFIX` / `NO_PREFIX`). Only the IT
  check is enforced; `hasValidCheckDigit(): ?bool` reports a national check where Task 0 read an official one (PL NIP,
  if read), else null.
- **Doctrine:** type names `org_<cc>_<snake_name>`; type classes flat, country-prefixed (`Orm\AuAbnType`,
  `Odm\EuVatNumberType`) because short names collide (`EU\VatNumber`, `GB\VatNumber`); `columnLength()` returns the
  class's `MAX_LENGTH`, and the test samples carry the length as a literal, so a changed constant fails a test.
- **Out of this plan:** the vo-base-bundle minor that adds the new registrars to `domain_types`, and the vo-finance
  minor for the Russian account key (both spec follow-ups).

## Review Focus

1. Formatted, spaced and lowercase input gives the compact canonical value, and `formatted()` gives back the print
   form: `12.abc.345/01de-35` → `12ABC34501DE35`, `51 824 753 556` → `51824753556`, `che-109.322.551` →
   `CHE109322551`, `de 123 456 789` → `DE123456789`, `12-3456789` → `123456789`.
2. The alphanumeric CNPJ: `12ABC34501DE35` (RFB's example) valid with letters in positions 1-12; a letter in a check
   position refused; the numeric `11222333000181` still valid.
3. VAT prefixes: `EL123456789` accepted, `GR123456789` refused; `XI123456789` accepted, `GB123456789` refused by
   `EU\VatNumber` and accepted by `GB\VatNumber` (stored `123456789`); a bare number refused; `IT00743110158` (wrong
   check) refused.
4. Nine digits, the ambiguous length: no global factory; `RU\RegistrationNumberFactory` gives `Kpp`,
   `AU\BusinessIdentifierFactory` gives `Acn` (or refuses on its check), `EU\VatNumber` refuses; the RU factory's single refusal message names the vo-documents class for a
   person's INN whatever the input.
5. Leading zeros kept: `00743110157`, `000000019`, `00445790`, `01-2345678`; a 7-digit company number or a 10-digit
   partita IVA (a zero lost by an integer cast) is refused, never padded.

---

### Task 0: Official sources and the E / R / F decision per class

**Files:** `vo-organisations/` (`git init -b main`, nothing else yet), `vo-organisations/docs/sources/ORGANISATIONS.md`.

**Interfaces:** Produces the **Decision table** at the top of `ORGANISATIONS.md`: one row per class (class, E/R/F,
section link, read date UTC). Every later task reads its branch from that table; no task re-opens a decision.

- [ ] Read and quote (original language plus an English line, URL, read date UTC); an unreachable text gets what was
  tried and the fallback branch:
  - **Open items of the spec:** Минфин 165н (ГРН structure, ОГРН mod 11 and ОГРНИП mod 13) on publication.pravo.gov.ru;
    GB 32100-2015 on openstd.samr.gov.cn or a gov-hosted copy (alphabet, weights, check character); ASIC's ACN digit
    check page body; ISAP: the NIP regulation (MF) and the REGON regulation of 30.11.2015 (RM, annex with both
    weightings); the RFB "resto 0 ou 1 → DV 0" rule (alphanumeric FAQ or ADE COFIS 41/2017 Anexo II) and whether
    order `0001` means head office; the live VIES FAQ table (all 27 prefixes plus `XI`); INSEE on SIREN/SIRET (Luhn
    and the La Poste exception, with its rule if stated); NTA on the `T` + 13-digit qualified invoice issuer number
    (does the 法人番号 check apply to individuals' numbers?).
  - **Already read, quote again:** ABR "Format of the ABN"; UID-Verordnung Art. 5 and 8, eCH-0097 §2.4.2; DM
    23/12/1976 art. 8-9 (partita IVA: 7 serial, 3 office, 1 check); NTA 法人番号の構成; ПП 438 Прил. 1; FNS order
    ЕД-7-14/559@ (ИНН ЮЛ and КПП structure); SAT `tdCFDI.xsd` `t_RFC_PM`; IRS valid EIN prefixes (the table as of
    the read date).
  - **Structure only:** Companies House prefix list; HMRC VAT number forms (9, 12, `GD`, `HA`); CBIC/GSTN GSTIN
    structure (state code, PAN, entity, `Z`, check).
- [ ] Fill the Decision table. Fixed by the spec: E for `Abn`, `Uid`, `PartitaIva`, `CorporateNumber`, `Ogrn`; R for
  `Siren`, `Siret`; F for `CompanyNumber`, `GB\VatNumber`, `Gstin`, `LegalEntityRfc`, `LegalEntityInn`, `Kpp`, `Ein`,
  `EU\VatNumber` (IT enforced). Pending on the reads: `Acn` (E / R), `Cnpj` (E / R: E needs the 0-or-1 rule quoted),
  `UnifiedSocialCreditCode` (E / F), `Nip` and `Regon` (E / R), `Ogrnip` (E / R),
  `QualifiedInvoiceIssuerNumber` (built E, built F, or not built), `Cnpj::isHeadOffice()` (built or not),
  `Siret::hasValidCheckDigit()` for SIREN `356000000` (the quoted La Poste rule, or null).
- [ ] Add the "format only" and "left out" lists with reasons (spec "Left out" plus anything unreachable today).
- [ ] Commit `docs: organisation identifier sources and decisions`.

### Task 1: Scaffold both repositories

**Files:**
- `vo-organisations/`: tool configs (Global Constraints), `composer.json`, `composer-ci.json`, `composer-local.json`
  (gitignored), `deptrac.yaml`, `.editorconfig`, `.gitattributes`, `LICENSE`, `phpstan-baseline.neon`, `SECURITY.md`,
  `.github/workflows/checks.yml`, `CLAUDE.md` (stub), `src/Exception/InvalidOrganisationIdentifierException.php`,
  `src/Internal/{Reason,Input,Rules}.php`, `tests/Unit/OrganisationContractTest.php`, `tests/Unit/Internal/InputTest.php`;
- `vo-organisations-doctrine/` (`git init -b main`): the same set from `vo-finance-doctrine` (with
  `tools/check-autoload-isolation.php`, its `deptrac.yaml` and workflow with the MongoDB service), empty registrars
  `src/{Orm,Odm}/TypeRegistrar.php`.

**Interfaces:** Produces:
- `Input::compact(string $value, string $separators): string` (drops ASCII spaces and each byte of `$separators`,
  ASCII-uppercases, leaves any other byte for the shape check to refuse);
- `Rules::fail(non-empty-string $type, non-empty-string $reason): InvalidOrganisationIdentifierException`;
- `OrganisationContractTest::classes()`: rows `[class-string, longest sample, refused sample]`; every later task adds
  its rows.

- [ ] Copy the tool configs from the templates (not from a sibling) and run the `templates` job's compare script
  locally: no output, exit 0. `composer*.json` as vo-finance's with this package's name, description, keywords
  (`organisation`, `company-number`, `vat`, `tax-id`, `abn`, `cnpj`, `uid`, `siren`, `siret`, `gstin`, `ogrn`, `inn`,
  `kpp`, `ein`, `ddd`), no `ext-bcmath`, no `suggest`. `composer-local.json`: path repository `../vo-base`
  (symlink), `msstc4php/vo-base` as `dev-main as 1.4.0`; for the bridge also `../vo-base-doctrine` (`dev-main as
  1.3.0`) and `../vo-organisations` (`dev-main as 0.1.0`). `SECURITY.md`: "No version is released yet; `main` is
  supported until 1.0." `checks.yml`: vo-finance's, bundle-standard `ref: v4.2.0`, weekly `cron: '31 4 * * 1'` (bridge
  `'38 4 * * 1'`, workflow from vo-finance-doctrine with the comment that vo-organisations is reached through GitHub).
- [ ] Tests: `InputTest::testCompactDropsSpacesAndTheGivenSeparators`: `Input::compact(' 12.abc.345/01de-35 ', './-')`
  is `'12ABC34501DE35'`; `testCompactKeepsOtherCharactersForTheShapeCheck`: `Input::compact('12_3', '-')` is
  `'12_3'`; `testCompactDoesNotUppercaseNonAscii`: `Input::compact('ñ', '')` is `'ñ'`.
  `OrganisationContractTest`: `testTheExceptionIsFinalAndExtendsTheVoBaseBase` now; the row tests skip on an empty
  provider until Task 3 adds rows. For each row the class is `final`, extends
  `AbstractStringValueObject`, `MAX_LENGTH === mb_strlen(longest)`, `unserialize(serialize($vo))` equals it, and the
  refused sample throws `InvalidOrganisationIdentifierException` whose message contains neither the sample nor any
  3-character substring of it.
- [ ] Gates in both repos (the bridge's contract tests come in Task 13); commit `chore: scaffold` in each.

### Task 2: Check-digit helpers

**Files:** `src/Internal/{WeightedSum,Remainder,Luhn,AbnCheckDigits,AcnCheckDigit,CnpjCheckDigits,UidCheckDigit,UsccCheckCharacter,ItalianCheckDigit,CorporateNumberCheckDigit,NipCheckDigit,RegonCheckDigit,RegistrationNumberCheckDigit}.php`,
`tests/Unit/Internal/*Test.php`. Build `AcnCheckDigit`, `CnpjCheckDigits`, `NipCheckDigit`, `RegonCheckDigit`,
`RegistrationNumberCheckDigit` (mod 13) for either Task 0 branch (an R class reports with them); build
`UsccCheckCharacter` only on branch E.

**Interfaces:**
- `WeightedSum::of(string $chars, list<positive-int> $weights): non-negative-int`: Σ (ord(char) − 48) × weight,
  left-aligned (digits count 0-9, `A` 17 … `Z` 42, as the RFB states);
- `Remainder::of(numeric-string $digits, positive-int $modulus): non-negative-int`: digit by digit, no bcmath;
- every check class: `compute(string $body): string` (`\LogicException` on a malformed body) and
  `isValid(string $value): bool` (false on any malformed value). Exceptions: `UidCheckDigit::compute(): ?string`
  (null when the result is 10, "nicht vergeben"); `RegistrationNumberCheckDigit::compute(string $body, 11|13 $modulus)`
  and `isValid(string $value, 11|13 $modulus)` (the last digit of the remainder); `RegonCheckDigit` takes 8 or 13
  digits of body.

Rules not determined by the signatures: CNPJ weights `5 4 3 2 9 8 7 6 5 4 3 2` then `6 5 4 3 2 9 8 7 6 5 4 3 2`, DV =
11 − r, 0 when r < 2; UID weights `5 4 3 2 7 6 5 4`, 11 − r, r = 0 → 0; ABN first digit − 1, weights `10 1 3 5 7 9
11 13 15 17 19`, valid when Σ mod 89 = 0, so `compute()` of the 9-digit body is `10 + (89 − S mod 89) mod 89`;
法人番号 check = 9 − (Σ Pn × Qn mod 9), Pn from the right of the 12-digit base, Qn 1 for odd n and 2 for even n, the
check is the first digit; the conditional ones as Task 0 quotes them.

- [ ] Tests (official examples first, then the edge each rule has):
  ```php
  CnpjCheckDigits::compute('12ABC34501DE') === '35';      // RFB FAQ example
  CnpjCheckDigits::compute('112223330001') === '81';
  CnpjCheckDigits::compute('112223330000') === '09';      // r = 1 for the first DV → 0
  UidCheckDigit::compute('10932255') === '1';             // eCH-0097 CHE-109.322.551
  UidCheckDigit::compute('10000007') === '0';             // r = 0
  UidCheckDigit::compute('10000016') === null;            // 10: never issued
  AbnCheckDigits::compute('824753556') === '51';          // ABR 51 824 753 556
  AbnCheckDigits::isValid('51824753557') === false;
  CorporateNumberCheckDigit::compute('000012050002') === '7';  // NTA's own 7000012050002
  CorporateNumberCheckDigit::compute('100000000007') === '9';  // Σ mod 9 = 0
  ItalianCheckDigit::compute('0074311015') === '7';
  RegistrationNumberCheckDigit::compute('102770013219', 11) === '5';
  RegistrationNumberCheckDigit::compute('102770013213', 11) === '0';  // remainder 10
  RegistrationNumberCheckDigit::compute('30450011600018', 13) === '0'; // remainder 10
  RegistrationNumberCheckDigit::compute('30450011600015', 13) === '7';
  AcnCheckDigit::compute('00000001') === '9';             // ASIC's 000 000 019, if Task 0 quotes it
  NipCheckDigit::isValid('1234563218') === true;          // or the ISAP example
  RegonCheckDigit::compute('12345678') === '5'; RegonCheckDigit::compute('1234567851234') === '7';
  UsccCheckCharacter::compute('91350100M000100Y4') === '3';  // branch E; prefer GB 32100's own example
  Luhn::isValid('732829320') === true; Luhn::isValid('35600000049837') === false;
  ```
  Each `compute()` with a letter (where digits only) or a wrong length throws `\LogicException`; each `isValid()` of
  `''` is false. Replace an example by the official one wherever Task 0 quoted one, and keep the computed edges.
- [ ] Implement; gates; commit `feat: check-digit helpers`.

### Task 3: Australia

**Files:** `src/AU/{Abn,Acn,BusinessIdentifierFactory}.php`, tests, contract rows.

**Interfaces:**
- `Abn`: `MAX_LENGTH = 11`, separators spaces; `formatted(): non-empty-string` (`51 824 753 556`); `fromParts(string
  $body): self` (9 digits).
- `Acn`: `MAX_LENGTH = 9`, spaces; `formatted()` (`000 000 019`). E: `fromParts(string $body): self` (8 digits). R:
  `hasValidCheckDigit(): bool`.
- `BusinessIdentifierFactory` (private constructor): `fromString(string $value): Abn|Acn` (11 digits after
  `Input::compact` → `Abn`, 9 → `Acn`, else `InvalidOrganisationIdentifierException` `Australian business identifier
  has an invalid format.`); `tryFromString(string $value): Abn|Acn|null`.

- [ ] Tests: `Abn::from('51 824 753 556')` stored `51824753556`, `formatted()` round-trips; `Abn::from('51824753557')`
  throws with message `ABN has a wrong check digit.`; `Abn::fromParts('824753556')` equals it; `Acn::from('000 000
  019')` stored `000000019` (leading zeros kept); `Acn::from('00000019')` (8 digits) throws `ACN has an invalid
  format.`; E: `Acn::from('000000018')` throws, R: accepted with `hasValidCheckDigit()` false; factory: `'51 824 753
  556'` → `Abn`, `'000000019'` → `Acn`, `'123'` → `tryFromString()` null.
- [ ] Implement; gates; commit `feat: Australian ABN and ACN`.

### Task 4: Brazil

**Files:** `src/BR/Cnpj.php`, tests, contract rows.

**Interfaces:** `Cnpj`: `MAX_LENGTH = 14`; separators `.`, `/`, `-`, spaces; shape `^[0-9A-Z]{12}[0-9]{2}$`; `root():
non-empty-string` (8), `order(): non-empty-string` (4), `isAlphanumeric(): bool` (a letter in positions 1-12),
`formatted()` (`12.ABC.345/01DE-35`); `isHeadOffice(): bool` (`order() === '0001'`) only if Task 0 built it. E:
`fromParts(string $root, string $order): self`. R: `hasValidCheckDigit(): bool`.

- [ ] Tests: Review Focus 2; `Cnpj::from('12.abc.345/01de-35')` stored `12ABC34501DE35`, `root()` `12ABC345`,
  `order()` `01DE`, `isAlphanumeric()` true, `formatted()` `12.ABC.345/01DE-35`; `Cnpj::from('11.222.333/0001-81')`
  `isAlphanumeric()` false; `Cnpj::from('12ABC34501DEA5')` throws `CNPJ has an invalid format.`; `Cnpj::from('12ABC34501DE36')`
  throws `CNPJ has a wrong check digit.` (E) or is accepted with `hasValidCheckDigit()` false (R);
  `Cnpj::fromParts('11222333', '0000')` gives `11222333000009` (E); `Cnpj::from('12_ABC34501DE35')` throws.
- [ ] Implement; gates; commit `feat: Brazilian CNPJ, numeric and alphanumeric`.

### Task 5: Switzerland and China

**Files:** `src/CH/Uid.php`, `src/CN/UnifiedSocialCreditCode.php`, tests, contract rows.

**Interfaces:**
- `Uid`: `MAX_LENGTH = 12`; separators `-`, `.`, spaces; the `CHE` prefix required; `formatted()`
  (`CHE-109.322.551`); `fromParts(string $serial): self` (8 digits; a serial whose check would be 10 throws
  `Swiss UID has a check digit that is never issued.`).
- `UnifiedSocialCreditCode`: `MAX_LENGTH = 18`; spaces; alphabet `0-9A-HJ-NPQRTUWXY` (as Task 0 quotes it);
  `registrationAuthorityCode()` (1), `organisationTypeCode()` (1), `administrativeDivisionCode()` (6),
  `organisationCode()` (9), all `non-empty-string`. E: `fromParts(string $body): self` (17 characters). F: no check.

- [ ] Tests: `Uid::from('che-109.322.551')` and `Uid::from('CHE109322551')` equal, stored `CHE109322551`;
  `Uid::from('109322551')` throws (no prefix); `Uid::from('CHE-109.322.551 MWST')` throws (the supplement is not part
  of the value, UID-Verordnung Art. 8); `Uid::from('CHE100000160')` throws `Swiss UID has a check digit that is never issued.` (serial `10000016`); `Uid::fromParts('10000007')` gives `CHE100000070`. USCC: `UnifiedSocialCreditCode::from('91350100m000100y43')`
  stored uppercase, decoders `9`, `1`, `350100`, `M000100Y4`; `…Y4I` (an `I`) throws `invalid format`; E: `…Y44`
  throws `USCC has a wrong check character.`; use GB 32100's own example if Task 0 found one.
- [ ] Implement; gates; commit `feat: Swiss UID and Chinese unified social credit code`.

### Task 6: France

**Files:** `src/FR/{Siren,Siret}.php`, tests, contract rows.

**Interfaces:** `Siren`: `MAX_LENGTH = 9`, spaces, `formatted()` (`732 829 320`), `hasValidCheckDigit(): bool`
(Luhn). `Siret`: `MAX_LENGTH = 14`, spaces, `siren(): Siren`, `nic(): non-empty-string` (5), `formatted()`
(`732 829 320 00074`), `hasValidCheckDigit(): ?bool` (Luhn; for SIREN `356000000` the La Poste rule if Task 0 quoted
it, else null).

- [ ] Tests: `Siret::from('732 829 320 00074')` stored `73282932000074`, `siren()` equals `Siren::from('732829320')`,
  `nic()` `00074`, `hasValidCheckDigit()` true; `Siren::from('732829321')` accepted, `hasValidCheckDigit()` false
  (never refused); `Siret::from('35600000049837')` accepted, `hasValidCheckDigit()` null (or true under the quoted
  rule); `Siren::from('73282932')` throws `SIREN has an invalid format.`
- [ ] Implement; gates; commit `feat: French SIREN and SIRET`.

### Task 7: United Kingdom

**Files:** `src/GB/{CompanyNumber,VatNumber}.php`, tests, contract rows.

**Interfaces:**
- `CompanyNumber`: `MAX_LENGTH = 8`; spaces; the shapes Task 0 quotes from Companies House (default `[0-9]{8}`,
  two capital letters + 6 digits, `R` + 7 digits); `prefix(): ?non-empty-string` (the letters, null when all digits;
  decoded, never refused).
- `VatNumber`: `MAX_LENGTH = 12`; spaces; an optional leading `GB` dropped; `[0-9]{9}`, `[0-9]{12}`, `GD[0-9]{3}`,
  `HA[0-9]{3}`; `isBranchTraderNumber(): bool` (12 digits), `isGovernmentDepartment(): bool` (`GD`),
  `isHealthAuthority(): bool` (`HA`). No check (HMRC publishes none).

- [ ] Tests: `CompanyNumber::from('00445790')` stored with its zeros, `prefix()` null; `CompanyNumber::from('sc123456')`
  `prefix()` `SC`; `CompanyNumber::from('445790')` throws; `VatNumber::from('GB 123 4567 89')` stored `123456789`;
  `VatNumber::from('123456789001')->isBranchTraderNumber()` true; `VatNumber::from('GD001')->isGovernmentDepartment()`
  true; `VatNumber::from('XI123456789')` throws (Northern Ireland is `EU\VatNumber`).
- [ ] Implement; gates; commit `feat: UK company number and VAT number`.

### Task 8: India, Italy, Japan, Mexico

**Files:** `src/IN/Gstin.php`, `src/IT/PartitaIva.php`, `src/JP/CorporateNumber.php`,
`src/JP/QualifiedInvoiceIssuerNumber.php` (if Task 0 built it), `src/MX/LegalEntityRfc.php`, tests, contract rows.

**Interfaces:**
- `Gstin`: `MAX_LENGTH = 15`; spaces; the structure Task 0 quotes (default `[0-9]{2}` + PAN shape `[A-Z]{5}[0-9]{4}
  [A-Z]` + `[1-9A-Z]` + `Z` + `[0-9A-Z]`; if no official structure text, `[0-9]{2}[0-9A-Z]{13}`); `stateCode()` (2),
  `pan()` (10, a string, no vo-documents type), `entityCode()` (1).
- `PartitaIva`: `MAX_LENGTH = 11`; spaces; `serialNumber()` (7), `officeCode()` (3), `fromParts(string $serialNumber,
  string $officeCode): self`. The `IT` prefix is refused (that is `EU\VatNumber`).
- `CorporateNumber`: `MAX_LENGTH = 13`; spaces and `-`; `baseNumber()` (12), `fromParts(string $baseNumber): self`.
- `QualifiedInvoiceIssuerNumber` (built E or F per Task 0): `MAX_LENGTH = 14`; `T` + 13 digits, `t` uppercased,
  spaces and `-`; `digits(): non-empty-string` (13). E only if NTA states the 法人番号 check applies to every issued
  number, individuals included.
- `LegalEntityRfc`: `MAX_LENGTH = 12` (characters); SAT `t_RFC_PM` as Task 0 quotes it (`[A-Z&Ñ]{3}[0-9]{2}(0[1-9]|
  1[012])(0[1-9]|[12][0-9]|3[01])[A-Z0-9]{2}[0-9A]`); ASCII letters and `ñ` uppercased; spaces and `-`;
  `incorporationDateDigits(): non-empty-string` (`YYMMDD` raw: the century is not in the value, so no date object).

- [ ] Tests: `Gstin::from('27aapfu0939f1zv')` stored uppercase, `stateCode()` `27`, `pan()` `AAPFU0939F`,
  `entityCode()` `1`; `PartitaIva::from('00743110157')` (zeros kept), `officeCode()` `015`; `PartitaIva::from(
  '00743110158')` throws `partita IVA has a wrong check digit.`; `PartitaIva::from('743110157')` throws;
  `CorporateNumber::from('7000012050002')->baseNumber()` `000012050002`; `CorporateNumber::from('8000012050002')`
  throws; `CorporateNumber::fromParts('100000000007')` gives `9100000000007`; `QualifiedInvoiceIssuerNumber::from(
  't7000012050002')` stored `T7000012050002`; `LegalEntityRfc::from('ñab010101ab1')` stored `ÑAB010101AB1` and fits
  `MAX_LENGTH` in characters (13 bytes); `incorporationDateDigits()` `010101`; a 13-character person RFC throws.
- [ ] Implement; gates; commit `feat: GSTIN, partita IVA, Japanese corporate number, legal-entity RFC`.

### Task 9: Poland and Russia

**Files:** `src/PL/{Nip,Regon}.php`, `src/RU/{Ogrn,Ogrnip,LegalEntityInn,Kpp,RegistrationNumberFactory}.php`,
tests, contract rows.

**Interfaces:**
- `Nip`: `MAX_LENGTH = 10`; `-` and spaces; the `PL` prefix refused. E: `fromParts(string $body): self` (9 digits).
  R: `hasValidCheckDigit(): bool`.
- `Regon`: `MAX_LENGTH = 14`; 9 or 14 digits; `isLocalUnit(): bool` (14), `parentRegon(): ?self` (the first 9 of a
  14-digit value, null for 9). E: `fromParts(string $body): self` (8 or 13 digits). R: `hasValidCheckDigit(): bool`.
- `Ogrn`: `MAX_LENGTH = 13`; first digit `1` or `5`; `registrationYearDigits()` (positions 2-3, no century),
  `regionCode()` (positions 4-5); `fromParts(string $body): self` (12 digits).
- `Ogrnip`: `MAX_LENGTH = 15`; first digit `3`; `registrationYearDigits()`, `regionCode()`. E: `fromParts(string
  $body): self` (14 digits). R: `hasValidCheckDigit(): bool`. No `masked()`.
- `LegalEntityInn`: `MAX_LENGTH = 10`; `taxAuthorityRegionCode()` (2); format only; ten zeros refused only if Task 0
  quotes an official "filler" statement for them.
- `Kpp`: `MAX_LENGTH = 9`; `[0-9]{4}[0-9A-Z]{2}[0-9]{3}`; `taxOfficeCode()` (4), `reasonCode()` (2).
- `RegistrationNumberFactory` (private constructor): `fromString(string $value): Ogrn|Ogrnip|LegalEntityInn|Kpp` by
  length after `Input::compact($value, '')`: 13, 15, 10, 9; every other input (12 digits included) throws the single message of
  "Messages" above; `tryFromString(string $value):
  Ogrn|Ogrnip|LegalEntityInn|Kpp|null`.

- [ ] Tests: `Nip::from('123-456-32-18')` stored `1234563218`; E: `Nip::from('1234563219')` throws, R: accepted with
  `hasValidCheckDigit()` false; `Regon::from('12345678512347')->parentRegon()` equals `Regon::from('123456785')`;
  `Ogrn::from('1027700132195')` (Sberbank), `regionCode()` `77`; `Ogrn::from('1027700132196')` throws `OGRN has a wrong
  check digit.`; `Ogrn::from('1027700132130')` valid (remainder 10 → 0); `Ogrn::from('2027700132195')` throws
  `OGRN has an invalid format.` (first digit not 1 or 5); `Ogrnip::from('304500116000157')` valid; `Ogrnip::from('304500116000180')`
  valid (remainder 10); `LegalEntityInn::from('7707083893')`; `Kpp::from('7736 01 001')` stored `773601001`,
  `reasonCode()` `01`; `Kpp::from('7736AB001')` accepted; factory: `'1027700132195'` → `Ogrn`, `'773601001'` → `Kpp`,
  `'7707083893'` → `LegalEntityInn`, `'500100732259'` and `'12345'` throw the same single message (assert both equal), `'12345'` → null
  from `tryFromString()`.
- [ ] Implement; gates; commit `feat: Polish NIP and REGON, Russian OGRN, OGRNIP, INN and KPP`.

### Task 10: United States

**Files:** `src/US/Ein.php`, `src/Internal/EinPrefixes.php`, tests, contract rows.

**Interfaces:** `Ein`: `MAX_LENGTH = 9`; `-` and spaces; `prefix(): non-empty-string` (2), `formatted()`
(`12-3456789`), `isAssignedPrefix(): bool` (report only). `EinPrefixes::isAssigned(string $prefix): bool` holds the
IRS table as Task 0 read it, with the read date in its docblock.

- [ ] Tests: `Ein::from('12-3456789')` stored `123456789`, `formatted()` `12-3456789`, `isAssignedPrefix()` true;
  `Ein::from('01-2345678')` keeps the zero; a prefix absent from the IRS table (one Task 0 recorded, for example
  `07`) is accepted with `isAssignedPrefix()` false; `Ein::from('12345678')` throws.
- [ ] Implement; gates; commit `feat: US EIN`.

### Task 11: EU VAT number

**Files:** `src/EU/VatNumber.php`, `src/Internal/ViesFormats.php`, tests, contract rows.

**Interfaces:**
- `ViesFormats::pattern(string $prefix): ?non-empty-string`: the national-body regex per VIES prefix (`AT` … `SK`,
  `EL`, `XI`) exactly as Task 0 transcribed the live table (`ATU` + 8 digits, `BE` + `[01]` + 9 digits, `ES` with
  first and last not both digits, `FR` + 2 letters-or-digits + 9 digits, `NL` + 12 of letter, digit, `+`, `*`, `XI`
  the `GB\VatNumber` forms); null for any other prefix.
- `VatNumber`: `MAX_LENGTH = 14`; separators spaces, `.`, `-`; `countryPrefix(): non-empty-string` (`EL`, `XI` as
  given), `country(): CountryCode` (vo-base; `EL` → `GR`, `XI` → `GB`), `nationalNumber(): non-empty-string`,
  `hasValidCheckDigit(): ?bool` (IT: always true, the check is enforced; PL: `NipCheckDigit` if Task 0 read it; else
  null). Messages: `EU VAT number has no country prefix.`, `EU VAT number has an unknown country prefix.`,
  `EU VAT number has an invalid format.`, `EU VAT number has a wrong check digit.` (IT).

- [ ] Tests: Review Focus 3; one valid and one invalid example per prefix from the VIES table (data provider, 28
  rows); `VatNumber::from('de 123.456-789')` stored `DE123456789`, `country()` `CountryCode::DE`;
  `VatNumber::from('EL123456789')->country()` `CountryCode::GR`; `VatNumber::from('XIGD001')->country()`
  `CountryCode::GB`; `VatNumber::from('IT00743110157')->hasValidCheckDigit()` true; `VatNumber::from('FR40303265045')`
  accepted, `hasValidCheckDigit()` null; `VatNumber::from('ES12345678Z')` accepted, `VatNumber::from('ES123456789')`
  throws; `VatNumber::from('AT12345678')` throws (no `U`); `VatNumber::from('123456789')` throws `NO_PREFIX`; the
  longest accepted value (NL or XI branch) is 14 characters.
- [ ] Implement; gates; commit `feat: EU VAT identification number`.

### Task 12: Package documentation

**Files:** `vo-organisations/{README.md,CHANGELOG.md,CLAUDE.md}`, `.claude/docs/{README,architecture,domain-model,integrations,known-issues,conventions}.md`.

- [ ] `README.md` sections, in vo-finance's order: intro (source rule, link to `ORGANISATIONS.md`); Status ("0.1.0,
  pre-1.0: a minor may break, each break listed in the CHANGELOG"); Install (GitHub VCS repositories); Usage; Classes
  (one table: class, source, E/R/F, value and accessors); Canonical forms and accepted separators; Factories (why
  there is no global one: the nine-digit list of the spec); Format only (with reasons); Left out (spec list plus Task
  0's unreachable texts); **Backward compatibility**: the 0.x rule now, and the 1.x promise drafted for 1.0.0 in
  vo-finance's wording (classes, method and parameter names, canonical strings, `MAX_LENGTH`, bridge type names and
  column lengths, exception classes; a minor never refuses a value an earlier 1.x accepted, a later check is a
  `hasValid…(): bool`; factory candidate sets frozen per major; the EIN prefix table and the VIES formats ship in
  minors and only widen; not covered: message wording, `Internal\`); With the bridges; License.
- [ ] `CHANGELOG.md` `## [Unreleased]` → `### Added` per area. `CLAUDE.md` in vo-identifiers' shape (what this is,
  commands incl. `composer-local.json` recipe, Standard, the Decision table as the authority, gotchas: the RU
  factory's single refusal message (no length leak), `EL`/`XI`, no zero padding). `.claude/docs` as vo-finance's set, with the Task 0
  findings that cost time in `known-issues.md`.
- [ ] Commit `docs: vo-organisations`.

### Task 13: Doctrine types

**Files:** `vo-organisations-doctrine/src/{Orm,Odm}/<Cc><Class>Type.php` (21, plus `JpQualifiedInvoiceIssuerNumberType`
if built), `src/{Orm,Odm}/TypeRegistrar.php`, `tests/Support/{Samples.php,Orm/Organisation.php,Odm/Organisation.php}`,
`tests/Unit/{Orm,Odm}/{TypeRegistrarTest,StringTypesTest}.php`,
`tests/Unit/Contract/{EveryOrganisationClassHasATypeTest,ColumnLengthsFollowTheLibraryBoundsTest}.php`,
`tests/Integration/{OrmRoundTripTest,OdmRoundTripTest}.php`.

**Interfaces:** Consumes every value object of Tasks 3-11. Names and lengths (`NAME` constant, `columnLength()` returns
the class's `MAX_LENGTH`): `org_au_abn` 11, `org_au_acn` 9, `org_br_cnpj` 14, `org_ch_uid` 12,
`org_cn_unified_social_credit_code` 18, `org_eu_vat_number` 14, `org_fr_siren` 9, `org_fr_siret` 14,
`org_gb_company_number` 8, `org_gb_vat_number` 12, `org_in_gstin` 15, `org_it_partita_iva` 11,
`org_jp_corporate_number` 13, (`org_jp_qualified_invoice_issuer_number` 14), `org_mx_legal_entity_rfc` 12,
`org_pl_nip` 10, `org_pl_regon` 14, `org_ru_ogrn` 13, `org_ru_ogrnip` 15, `org_ru_legal_entity_inn` 10,
`org_ru_kpp` 9, `org_us_ein` 9. Both registrars: `registerAll(): void` (idempotent), `types(): array<non-empty-string,
class-string<Type>>`, the same keys in the same order.

- [ ] Tests, as vo-finance-doctrine's: `Samples::strings()` rows `[name, canonical sample, literal length,
  BAD_STORED, at-max]`; `TypeRegistrarTest::testNamesFollowThePrefixConvention` with
  `/^org_[a-z]{2}_[a-z0-9]+(?:_[a-z0-9]+)*$/`; `StringTypesTest` round trip, null, refused stored value without
  echoing it, foreign class and plain string refused on write; `EveryOrganisationClassHasATypeTest` (exactly one type
  per value object per branch, nothing outside the package, factories and `Internal\` excluded, the count 21 or 22);
  `ColumnLengthsFollowTheLibraryBoundsTest`: `VARCHAR(MAX_LENGTH)` on SQLite for every type;
  `OrmRoundTripTest`: the schema declares `cnpj VARCHAR(14)` and `rfc VARCHAR(12)`, a CNPJ given lowercase with
  punctuation is stored `12ABC34501DE35`, and `ÑAB010101AB1` survives a flush and reload; `OdmRoundTripTest` the same
  with MongoDB (skips without `MONGODB_URI`). `php tools/check-autoload-isolation.php src/Orm …` and `src/Odm` pass.
- [ ] Implement; gates with the local profile; `README.md` (Install, Usage, the type table, Backward compatibility:
  0.x now, the 1.x freeze of names and lengths drafted), `CHANGELOG.md`, `CLAUDE.md` ("Adding a class of
  vo-organisations", as vo-finance-doctrine's), `.claude/docs`; commit `feat: Doctrine types for vo-organisations`.

### Task 14: Review

The controller runs this task itself, one agent at a time.

- [ ] PhpStorm `get_inspections` on every PHP file of both repositories if the MCP is reachable (otherwise say so);
  fix actionable findings.
- [ ] `/acc:code-review high` on each repository; fix every finding, Suggestions included; re-run the review when the
  fixes are non-trivial. Check by hand: every refused sample's message against the input, every `@see` against
  `ORGANISATIONS.md`, every E class against the Decision table.
- [ ] Refresh the local tools, gates in both repositories; commit `fix: review of 0.1.0`.

### Task 15: Push, CI and tag 0.1.0

Gated on the owner: the GitHub repositories `msstc4php/vo-organisations` and `msstc4php/vo-organisations-doctrine` do
not exist yet. Until the owner says they exist, stop here and ask (`spd-say "Need review"`).

- [ ] `git remote add origin git@github.com:msstc4php/vo-organisations.git` (and `…-doctrine.git`); refresh the local
  tools; gates; `git push -u origin main` for `vo-organisations`; all CI jobs green (templates, check, PHPUnit 8.4/8.5
  highest and 8.4 lowest, BC check skips without a tag, Infection).
- [ ] `vo-organisations` `CHANGELOG.md` `## [0.1.0] - <date UTC>`, README status line, commit `docs: release 0.1.0`,
  push, green CI; **ask the owner**, then tag `v0.1.0` and push the tag.
- [ ] `vo-organisations-doctrine`: push; the jobs that need `vo-organisations ^0.1` from GitHub pass once the tag
  exists (re-run them); changelog `0.1.0`, push, green CI; **ask the owner**, then tag `v0.1.0`. GitHub release pages
  if `gh` is authenticated; Packagist is the owner's.
