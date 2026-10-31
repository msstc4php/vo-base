---
date: 2026-07-20
type: refactor
title: "refactor: VO quality hardening (v1.1.x)"
origin: docs/brainstorms/2026-07-20-vo-quality-hardening-requirements.md
depth: standard
---

# refactor: VO Quality Hardening (v1.1.x)

Close out the ten v1.1 code-review findings, tighten test rigor without adding
any dependency, and refresh docs. Additive and non-breaking; grows reliability,
not API surface.

Origin: `docs/brainstorms/2026-07-20-vo-quality-hardening-requirements.md`.

---

## Problem Frame

v1.1 passed high-level review with "approve with comments": 0 critical/major,
5 minor, 5 suggestions (CR-001..CR-010). Per the project's close-out rule every
finding is a TODO. Separately, mutation coverage (MSI ~71%) mixes genuine gaps
with **equivalent** mutants that cannot be killed and only add noise. This
iteration lands the finding fixes, replaces the noise with honest ignores, adds
invariant tests for the algebraic properties, and puts a CI floor under MSI so
quality can't silently regress before the library is released and dogfooded.

---

## Requirements Traceability

| Origin req / finding | Covered by |
|---|---|
| R1 / CR-001 (isPublic reserved ranges + SSRF warning) | U1 |
| R4 / CR-004 (IntRange::length overflow) | U2 |
| R2 / CR-002, R3 / CR-003, R5 / CR-005..008, CR-010 (docblock/semantic) | U3 |
| R5 / CR-009 (ClassifiesIpAddress location) | U4 |
| R6 + R8 (mutant triage, equivalent-ignore, CI min-MSI floor) | U5 |
| R7 (algebraic invariant tests) | U6 |
| R9 (documentation refresh) | U7 |

---

## Key Technical Decisions

- **KTD1. `isPublic()` = expand reserved ranges AND add SSRF warning
  (CR-001).** Exclude the well-known IANA special-purpose ranges *and*
  docblock-warn that `isPublic()` is not a safe SSRF allow/deny gate. Both,
  not one: correctness fix plus an honest applicability boundary so security
  never silently depends on an incomplete table. (see origin: KD2)
- **KTD2. `IntRange::length()` guards overflow with a throw (CR-004).** Detect
  the wrapped-negative result of `max - min + 1` and throw `\RangeException`
  rather than letting `max(1, …)` silently clamp to 1. (see origin: R4)
- **KTD3. Zero new dependencies (CR test rigor).** Invariant tests use PHPUnit
  data-providers and small seeded helpers — no Eris, no ext-gmp. (see origin: KD1)
- **KTD4. Equivalent mutants are ignored with rationale, not chased (R6).**
  `(int) substr($v,0,2)` → `(int) $v` on canonical `HH:MM:SS`, and unreachable
  `inet_pton(...) === false` guards on already-validated input, are genuinely
  equivalent. Mark them ignored (mechanism decided at implementation); close
  only the real gaps. Chasing 100% MSI on equivalents is waste. (see origin: KD4)
- **KTD5. `hostCount()` IPv6 stays throw-only, docblock sharpened (CR-002).**
  No string/GMP variant this iteration — it would breach KTD3. Just make the
  docblock state `/64` and wider throw. (see origin: KD3)
- **KTD6. No public-signature changes.** Only internal ranges, docblocks,
  a new guard throw, tests, CI, and a trait relocation. `isPublic()` returning
  a different boolean for reserved input is a correctness fix, not a contract
  change.

---

## Implementation Units

### U1. Expand `isPublic()` reserved ranges + SSRF warning

**Goal:** `isPublic()` no longer reports reserved/special-purpose addresses as
public, and its docstring states it is not an SSRF gate.
**Requirements:** R1 / CR-001.
**Dependencies:** none.
**Files:**
- `src/Network/Ipv4Address.php` (extend the private range predicates / add a
  reserved-range check feeding `isPublic`)
- `src/Network/Ipv6Address.php` (same for v6)
- `src/Support/ClassifiesIpAddress.php` (SSRF docblock warning on `isPublic`)
- `tests/Unit/Network/Ipv4AddressTest.php`,
  `tests/Unit/Network/Ipv6AddressTest.php`,
  `tests/Unit/Network/IpAddressTest.php` (reserved-range table)
**Approach:** Add an `isReserved()` (or fold into the existing predicate set)
covering the IANA special-purpose ranges below via the existing `inRange()`
CIDR helper, and make `isPublic()` exclude it. The exact registry list is
finalized at implementation against the IANA special-purpose-address registries
(Open Questions); the plan's target set:

| Family | Ranges to treat as non-public |
|---|---|
| IPv4 | `0.0.0.0/8`, `100.64.0.0/10` (CGNAT), `192.0.0.0/24`, `192.0.2.0/24`, `198.18.0.0/15`, `198.51.100.0/24`, `203.0.113.0/24`, `240.0.0.0/4`, `255.255.255.255/32` (plus existing private/loopback/link-local/multicast) |
| IPv6 | `::/128` (unspecified), `::ffff:0:0/96` (v4-mapped), `2001:db8::/32` (documentation), `64:ff9b::/96` (plus existing ULA/loopback/link-local/multicast) |

Keep the derivation in `isPublic()` (negation) so adding a range is one line.
The SSRF caveat is a `@` docblock note on `ClassifiesIpAddress::isPublic()`.
**Patterns to follow:** existing `inRange()` / predicate methods in
`Ipv4Address`/`Ipv6Address` (U3 of the v1.1 work).
**Test scenarios:**
- Covers CR-001. Reserved v4 → not public: `0.0.0.0`, `100.64.0.1`,
  `240.0.0.1`, `255.255.255.255`, `192.0.2.5`, `198.18.0.1`.
- Reserved v6 → not public: `::`, `::ffff:192.0.2.1`, `2001:db8::1`.
- Still public: `8.8.8.8`, `1.1.1.1`, `2606:4700::1` (regression guard — the
  expansion must not over-exclude routable space).
- Existing private/loopback/link-local/multicast classifications unchanged.
- `IpAddress` delegation reflects the new results for both families.
**Verification:** No reserved/special-purpose address returns `isPublic() ===
true`; routable addresses still do; docblock carries the SSRF warning.

### U2. Guard `IntRange::length()` overflow

**Goal:** Extreme bounds cannot silently yield `1`.
**Requirements:** R4 / CR-004.
**Dependencies:** none.
**Files:**
- `src/Primitive/IntRange.php`
- `tests/Unit/Primitive/IntRangeTest.php`
**Approach:** Compute `max - min + 1`; if the arithmetic overflowed (result is
negative while `max >= min`, or detect via comparison before adding), throw
`\RangeException` with a clear message instead of `max(1, …)` clamping. Keep the
normal path returning `positive-int`.
**Patterns to follow:** the `\RangeException` guard style already used in
`Cidr::hostCount()`.
**Test scenarios:**
- Covers CR-004. Happy: `[1,5]`→5, `[3,3]`→1 (unchanged).
- Edge/error: `IntRange(PHP_INT_MIN, PHP_INT_MAX)->length()` throws
  `\RangeException` (does not return 1).
- Boundary just under overflow returns the correct large count.
**Verification:** Overflow throws; all existing `length()` results unchanged.

### U3. Docblock & semantic completeness sweep

**Goal:** Documentation matches real behavior for the remaining findings.
**Requirements:** R2/CR-002, R3/CR-003, R5/CR-005, CR-006, CR-007, CR-008, CR-010.
**Dependencies:** none.
**Files:**
- `src/Network/Cidr.php` (CR-002: `hostCount()` docblock — IPv6 `/64` and wider
  throw)
- `src/Primitive/Percentage.php` (CR-003: add `@throws InvalidDecimalException`
  on `fromRatio()` for zero denominator)
- `src/Temporal/DateRange.php` (CR-005: `duration()` note — seconds bucket only,
  sub-second precision dropped)
- `src/Format/SemVer.php` (CR-006: `nextPatch()` note — on a pre-release input it
  skips the eventual release)
- `src/Temporal/TimeOfDay.php` (CR-007: `compareTo()` note — foreign-type throw
  is intentional, consistent with the library's `Comparable` implementations)
- `src/Support/ComparableHelpers.php` (CR-008: note that mixing VO types is
  caller error)
- `src/Network/Ipv4Address.php` / `src/Network/IpAddress.php` (CR-010: brief note
  that predicates allocate per call; no caching by decision)
**Approach:** Pure docblock edits — no behavior change. CR-003 adds a real
missing `@throws`. CR-007/CR-008/CR-010 are documented decisions ("intentional",
"caller error", "not cached") rather than code changes.
**Patterns to follow:** existing why-not-what docblock style; `@throws` ordering
already used across the library.
**Test scenarios:** `Test expectation: none -- docblock-only edits, no behavioral
change.` (CR-003's zero-denominator throw is already covered by
`PercentageTest::testFromRatioDivideByZeroThrows`; verify it still asserts the
exception type named in the new `@throws`.)
**Verification:** PHPStan clean; `Percentage::fromRatio(1, 0)` still throws
`InvalidDecimalException` and the docblock now lists it.

### U4. Relocate `ClassifiesIpAddress` to `Network`

**Goal:** Resolve CR-009 — a `Support` trait importing concrete `Network` types
inverts the "support is leaf" expectation.
**Requirements:** R5 / CR-009.
**Dependencies:** none (independent of U1's range edits, but touches the same
files — sequence after U1 to avoid churn).
**Files:**
- `src/Support/ClassifiesIpAddress.php` → `src/Network/ClassifiesIpAddress.php`
  (move; update namespace)
- `src/Network/Ipv4Address.php`, `src/Network/Ipv6Address.php` (update `use`)
- `deptrac.yaml` (verify no rule change needed — both are Core)
**Approach:** Straight move into the `Network` namespace where its `Cidr`/
`IpAddress` collaborators live; the trait stops crossing namespaces upward.
Confirm deptrac still passes (Core→Core, no Bridge involvement).
**Patterns to follow:** the other `Network`-local helpers.
**Test scenarios:** `Test expectation: none -- pure relocation; existing
Ipv4/Ipv6/IpAddress tests prove behavior is unchanged.`
**Verification:** deptrac + PHPStan + full suite green after the move; no
references to the old FQCN remain (`grep`).

### U5. Mutation triage, equivalent-mutant ignores, CI min-MSI floor

**Goal:** Kill the genuine escaped mutants, silence the equivalent ones with
rationale, and gate MSI in CI.
**Requirements:** R6, R8, KTD4.
**Dependencies:** U1, U2, U3, U4 (run triage on the settled code).
**Files:**
- `infection.json5` (min-MSI / min-covered-MSI thresholds; ignore config if that
  mechanism is chosen)
- affected `src/**` (targeted `@infection-ignore-all` or per-mutator annotations
  only where the mutant is genuinely equivalent, each with a one-line reason)
- affected `tests/Unit/**` (new assertions closing the real gaps)
- `.gitlab-ci.yml` (pass the min-MSI flags to the infection job)
**Approach:** Run infection; for each surviving mutant classify equivalent vs
real. Real → add a killing assertion. Equivalent → ignore with a documented
reason (mechanism — inline annotation vs `infection.json5` — chosen at
implementation, Open Questions). Set `--min-msi`/`--min-covered-msi` to the
level achievable after triage so CI fails on regression.
**Patterns to follow:** the mutation-strengthening tests added in v1.1
(`CidrTest`, `TimeOfDayTest`, `DurationTest`).
**Test scenarios:**
- Covers R6. Add killing tests for each mutant classified as real (e.g. any
  surviving `LogicalOr`/`LessThanOrEqualTo`/`Coalesce` in `Decimal`,
  `DateRange`, `Cidr`, `Duration` not already covered).
- Equivalent mutants: verify the ignore is scoped narrowly (does not mask
  neighbouring real mutations).
**Verification:** infection reports no *un-ignored* escaped mutants that are
real; CI job fails when MSI drops below the floor (proven by a local run at the
threshold); every ignore carries a reason.

### U6. Algebraic invariant tests (data-provider based)

**Goal:** Property-style coverage of the VO operations' invariants without a PBT
dependency.
**Requirements:** R7, KTD3.
**Dependencies:** none (can run alongside U5).
**Files:**
- `tests/Unit/Network/CidrTest.php`,
  `tests/Unit/Temporal/DurationTest.php`,
  `tests/Unit/Primitive/IntRangeTest.php`,
  `tests/Unit/Support/ComparableHelpersTest.php`
  (add data-provider-driven invariant tests)
- optional `tests/Support/` seeded helper if a small valid-VO generator reduces
  duplication (no external dep)
**Approach:** Encode the invariants as data-provider matrices:
- `Duration`: parse→plus/minus/multipliedBy→serialize→parse round-trips to an
  equal value across a table of inputs; `fromSeconds(n)->toSeconds()===n`.
- `Cidr`: `firstAddress()` and `lastAddress()` are always `contains()`ed;
  mask boundaries correct across a set of v4/v6 prefixes.
- `IntRange`: `intersect` is commutative (`a∩b == b∩a`); `contains` agrees with
  `overlaps` on singletons.
- `ComparableHelpers`: `min`/`max` agree with `compareTo` sign across a table
  spanning int/float/decimal/semver/time-of-day.
**Patterns to follow:** existing `#[DataProvider]` usage (`CidrTest`,
`SemVerTest`).
**Test scenarios:**
- Covers R7. Each invariant above as a `#[DataProvider]` case set with named
  rows; include boundary prefixes (`/0`, `/31`, `/32`, `/64`, `/128`) and
  zero/large durations.
- Negative rows where an invariant legitimately does not hold are excluded (not
  asserted false) to keep the property clean.
**Verification:** New invariant tests pass; they demonstrably fail if a relevant
operation is perturbed (spot-checked during implementation).

### U7. Documentation refresh

**Goal:** README and `.claude/docs` reflect real behavior and the SSRF caveat.
**Requirements:** R9.
**Dependencies:** U1 (SSRF wording), U2 (overflow behavior).
**Files:**
- `README.md` (behavior examples per group; zero-dep-network vs optional
  brick/math note already present — add the `isPublic()`/SSRF caveat)
- `.claude/docs/known-issues.md`, `.claude/docs/conventions.md` (align with the
  finding fixes: reserved-range expansion, length() guard, mutant-ignore policy)
**Approach:** Incremental edits, not a rewrite. Keep the README user-facing and
the `.claude/docs` engineer-facing.
**Patterns to follow:** existing README sections and the v1.1 doc entries.
**Test scenarios:** `Test expectation: none -- documentation only.`
**Verification:** Docs state the SSRF boundary, the IPv6 `/64` `hostCount`
throw, and the length() overflow behavior; no stale claims remain.

---

## Scope Boundaries

- **No new dependencies** (dev or runtime), including Eris and ext-gmp (KTD3).
- **No new VOs / no catalogue growth** — that's the next strategic track
  (release → dogfood → ergonomics → catalogue).
- **No public-signature changes** — only internal ranges, docblocks, one new
  guard throw, a trait relocation, tests, and CI (KTD6).
- **No 100% MSI chase** — equivalent mutants are ignored by design (KTD4).
- **No release** — tagging/Packagist is a separate later step.

### Deferred to Follow-Up Work

- `Cidr::hostCountString()` / GMP-backed count for wide IPv6 blocks (would breach
  KTD3; only the docblock is sharpened now).
- A reusable public Faker/test-factory surface for consumers (U6's seeded helper
  stays test-internal).

---

## Open Questions

### Resolve during implementation

- **[U1]** Final exact IANA special-purpose range list for `isPublic()` — verify
  against the current IANA IPv4/IPv6 special-purpose-address registries; the
  table above is the target, not necessarily exhaustive.
- **[U5]** Equivalent-mutant ignore mechanism under Infection 0.29 — inline
  `@infection-ignore-all` annotations vs a `infection.json5` ignore list; pick
  the narrowest that doesn't mask neighbouring real mutants.
- **[U5]** Concrete `--min-msi` / `--min-covered-msi` values — set from the
  post-triage achievable level (current baseline MSI ~71% / covered ~85%).

---

## System-Wide Impact

- **CI:** U5 adds an MSI floor to the infection job — future PRs that lower
  coverage will fail. Intended. Set the threshold at the achievable post-triage
  level, not aspirationally, to avoid false failures.
- **Consumers:** `isPublic()` returns `false` for reserved/special ranges it
  previously called public — a behavior change for anyone (incorrectly) relying
  on the old result. Call out in the changelog when released.

---

## Risks & Dependencies

- **Over-exclusion in U1.** Adding reserved ranges risks marking routable space
  non-public. Mitigation: explicit regression rows (`8.8.8.8`, `1.1.1.1`,
  `2606:4700::1`) stay public; verify against the IANA registry.
- **MSI floor set too high.** A threshold above the equivalent-mutant ceiling
  makes CI perpetually red. Mitigation: derive the value from a clean post-triage
  run (U5), not a round number.
- **Tooling gate.** Every unit passes `make check` (PHPStan 9, cs-fixer, rector,
  deptrac) + `make test`; infection re-run after U5. PhpStorm MCP inspections
  are unavailable in this environment (IDE not connected) — noted, PHPStan/
  cs-fixer/rector substitute.

---

## Sources & Research

- Origin requirements:
  `docs/brainstorms/2026-07-20-vo-quality-hardening-requirements.md`.
- v1.1 high-level code review (findings CR-001..CR-010), run this session.
- IANA IPv4/IPv6 Special-Purpose Address Registries (to be consulted at U1
  implementation for the exact range list).
- Existing v1.1 code and tests under `src/` and `tests/Unit/` (patterns to
  mirror; authored earlier this session).
