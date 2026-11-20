# IBAN Registry

What `Finance\Iban` takes from SWIFT's IBAN Registry, and on what terms.

## Source

SWIFT is the registration authority for ISO 13616 (the registry's own cover: "Registration Authority for ISO 13616 ...
SWIFT, has been designated by the ISO Technical Management Board to act as the Registration Authority for ISO 13616").

- Page: <https://www.swift.com/standards/data-standards/iban-international-bank-account-number>. It offers "the IBAN
  Registry (in PDF format) free of charge. The Registry is also available in .txt format."
- Files, from that page: `https://www.swift.com/swift-resource/11971/download` (TXT) and
  `https://www.swift.com/swift-resource/9606/download` (PDF).
- Reached on 2026-11-20 through the Internet Archive, because swift.com answers automated requests with HTTP 403:
  snapshot `20261103001712` of the page, and the files fetched in raw form (`.../web/20261103001712id_/<file URL>`),
  which the Archive serves unmodified.
- Edition: the page dates both files 2026-09-17. The TXT carries no release number; its newest per-country "Last
  update date" is Jun-26 (BH). The PDF on the same snapshot is older: "Release 99 - Dec 2024". The generated file
  records the TXT's SHA-256 (`a98aae4d7b59544973415491accb1345cb449bfd75a67c8bc704fbe6e161c303`), 89 countries.

## Terms of use (verdict: facts only, no redistribution)

The registry's legal notice (PDF, last page): "SWIFT (c) 2022. All rights reserved." and "SWIFT supplies this
publication for information purposes only. The information in this publication may change from time to time. You
must always refer to the latest available version."

swift.com's website terms (<https://www.swift.com/about-us/legal/online-services/swift-website-terms-use>, same
snapshot): "This Website contains proprietary Materials of S.W.I.F.T. SC and/or of its affiliates and/or their
respective licensors. All rights are reserved. Swift grants the swift.com User, however, a limited, personal,
non-transferable and revocable right to view, download and make available the Materials published on this Website
within the swift.com User's own organisation as may be reasonably necessary for the purpose for which it is supplied,
unmodified or unaltered in any way."

Neither text allows publishing the file or a derivative of it. So the repository does not contain the registry, and
the generated `src/Support/IbanRegistry.php` embeds only structural facts (length, BBAN character classes, the
positions of the bank and branch identifiers, the SEPA flag), typed by the generator from the file. The generator
reads a copy that the maintainer downloads. `tests/Fixtures/iban-registry-sample.txt` is a four-country excerpt in the
registry's layout written for the generator's tests. `tests/Fixtures/iban-registry-examples.php` holds the registry's
example IBANs, which the brief for this feature makes test cases.

## TXT layout

Tab-separated, `cp1252`, CRLF and LF mixed, with quoted multi-line cells (street addresses). One row per data
element, one column per country (the first column is the label). Rows the generator reads:

| Row | Example | Used for |
|---|---|---|
| `IBAN prefix country code (ISO 3166)` | `AD` | key |
| `SEPA country` | `Yes` / `No` | `isSepaCountry()` |
| `BBAN structure` | `4!n4!n12!c` | BBAN pattern; `n` digits, `a` upper-case letters, `c` upper- and lower-case letters and digits; `nn!` fixed length |
| `BBAN length` | `20` | cross-check; the IBAN length is this plus 4 |
| `IBAN length` | `24` | length check |
| `Bank identifier position within the BBAN` | `1-4` (one-based, inclusive) | `bankIdentifier()` |
| `Branch identifier position within the BBAN` | `5-8`; empty or `N/A` when none | `branchIdentifier()` |
| `IBAN electronic format example` | `AD1200012030200359100100` | generator sanity check; tests |

Cells carry trailing blanks (`4!n `) and example BBANs contain spaces; the generator trims. The `Bank identifier
pattern` and `Branch identifier pattern` rows are not used: the BBAN structure already fixes the characters.

## Registry anomalies met

- BA: the bank and branch identifier examples (`199`, `044`) belong to the BBAN example, not to the IBAN example.
- PL: the bank identifier example is empty and the 8-digit bank identifier sits in the branch example cell.
- SE: the bank identifier example `123` disagrees with the IBAN example (`500...`).
- IT and SM: the bank identifier starts at BBAN position 2 (a national check character comes first).
- FK: branch identifier position is `N/A`.
- Territories that share a country's code (for instance GF, GP under FR) have no entry of their own.

The tests read the identifiers for BA, PL and SE from the positions.

## Regenerating

    php tools/generate-iban-registry.php <path to the downloaded TXT> "<edition label>"

The edition label goes into the header of `IbanRegistry.php`. The generator refuses a registry whose structure,
lengths, positions, SEPA flag or example do not agree with each other.
