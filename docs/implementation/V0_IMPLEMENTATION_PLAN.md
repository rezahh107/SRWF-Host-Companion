# V0 Implementation Plan

**Status:** `APPROVED_SEQUENCE / EXECUTION_IN_PROGRESS`  
**Governing authority:** `docs/architecture/MOTHER_ARCHITECTURE.md` plus Owner-approved amendments  
**Purpose:** break the frozen V0 architecture into bounded implementation work without reopening architecture selection.

This plan is operational sequencing, not a second architecture authority.

Owner-approved qualification evidence direction is recorded in:

- `docs/architecture/AUTOMATED_QUALIFICATION_LAB.md`

## WU-00 — Repository foundation

Scope:

- canonical Mother Architecture in repository;
- PPDM adoption record;
- README and AGENTS operating contract;
- inert WordPress plugin bootstrap;
- repository hygiene files;
- PR template and changelog.

Exit evidence:

```text
Repository documents resolve
Bootstrap PHP parses
No runtime feature claim
```

Current status after foundation commit:

```text
FOUNDATION_ESTABLISHED
```

## WU-01 — Target runtime fact capture

Before feature implementation, record the exact target facts needed by V0:

- WordPress version;
- production PHP version;
- Twenty Twenty-Five version;
- relevant WordPress Block Template behavior;
- exact page-template assignment persistence/resolution behavior;
- deactivation/fallback behavior that affects the contract.

Outputs should distinguish:

```text
DOCUMENTED
RUNTIME_PROVEN
NOT_PROVEN
```

Do not freeze minimum PHP until the production host is inspected.

Current status on `main` after PR #3:

```text
COMPLETE_FOR_WU02
PRODUCTION_QUALIFICATION_NOT_PROVEN
```

The preserved production-host identity is WordPress `7.1.1`, PHP `8.3.33`, and active Twenty Twenty-Five `1.5`. Matching disposable-runtime page-template behavior is `RUNTIME_PROVEN`.

## WU-02 — Minimal runtime core

Implement only:

- plugin bootstrap/load structure required by real code;
- versioned configuration schema v1;
- canonical Registration template registration;
- exact page-template assignment adapter based on WU-01 evidence.

Do not add admin UI complexity beyond what WU-03 owns.

Do not add Operational template support.

Current status on `main` after PR #4:

```text
IMPLEMENTED_AND_INTEGRATION_PROVEN_ON_PINNED_TARGET_TUPLE
PRODUCTION_QUALIFICATION_NOT_PROVEN
```

Exact-head WU-02 CI on PR #4 exercised WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5`, and passed bootstrap/load, schema-v1 configuration, canonical template registration, exact assignment/readback, no-hidden-mutation, active render composition, and deactivation/fallback checks. Full Width geometry, Owner workflow, browser/E2E, accessibility, diagnostics/drift, and production qualification remained outside the WU-02 claim ceiling.

## WU-03 — Owner settings workflow

Implement:

- `Settings → SRWF Host`;
- first-run explanation;
- Registration Page selector;
- explicit `ذخیره و اعمال قالب تمام‌عرض` action;
- required authorization/capability/nonce checks;
- page validity classification;
- truthful save/apply result.

Normal use must not require Site Editor or code knowledge.

Current status on `main` after merged PR #6:

```text
IMPLEMENTED_AND_WORDPRESS_INTEGRATION_PROVEN_ON_PINNED_TARGET_TUPLE
MERGED_ON_MAIN
BROWSER_E2E_NOT_RUN
PRODUCTION_QUALIFICATION_NOT_PROVEN
```

PR #6 merged as `18fd0b3959301d2bd1066d255a9eb3f1897c199e`. Exact-head WU-03 CI exercised WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5`. It passed plugin load, settings-surface registration, unauthorized render blocking, side-effect-free render, published/non-published/missing/trashed/wrong-type classification, nonce failure, target-page `edit_post` denial, schema-v1 persistence, canonical assignment/readback, previous-page no-rewrite behavior, and bounded truthful result handling. Existing WU-01 and WU-02 exact-head regression workflows also passed on the same implementation head.

This is WordPress integration evidence, not a browser/E2E, Persian RTL visual, accessibility/comprehension, Full Width geometry, diagnostics/drift, or production-qualification claim.

## Automated Qualification Lab — cross-cutting evidence direction

WU-04 through WU-07 should move the largest practical share of repeatable technical qualification into the existing disposable CI/runtime-lab model, as defined by `docs/architecture/AUTOMATED_QUALIFICATION_LAB.md`.

Key execution rules:

- extend/reuse existing exact-runtime provisioning instead of building a parallel test platform;
- preserve per-WU evidence boundaries so one green workflow does not hide what was actually exercised;
- use real browser automation where browser/layout/keyboard evidence is required;
- retain useful failure artifacts such as screenshots, HTML, logs, browser traces and machine-readable evidence only when they improve diagnosis;
- keep unavailable licensed/proprietary dependency claims as `NOT_PROVEN` or `ENVIRONMENT_UNAVAILABLE` rather than faking equivalent fixtures;
- keep human comprehension and irreducibly production-specific confirmation outside CI substitution;
- do not let automated qualification alone imply `PRODUCTION_QUALIFIED_FOR_SRWF`;
- use focused checks on ordinary PRs and broader/full automated qualification at a justified regression or release boundary rather than forcing every expensive test on every trivial change.

The first real functional slice of this direction is WU-04: deterministic exact-target fixtures, state-specific non-mutation sentinels and a machine-readable evidence artifact are implemented without adding browser infrastructure before WU-05.

## WU-04 — Read-only diagnostics and drift

Implement:

- `Check Again` as read-only;
- expected-versus-resolved template state;
- `PAGE_VALID`, `PAGE_NOT_PUBLISHED`, `PAGE_INVALID` handling;
- canonical / DB override / theme override / missing / wrong assignment / unknown states as supported by real evidence;
- normalized block comparison if runtime evidence validates that approach;
- contextual next-action guidance;
- optional privacy-safe copyable diagnostic report.

No automatic repair in V0.

Qualification should use deterministic CI fixtures for the supported page/drift states and assert truthful evidence/interpretation/guidance plus no hidden mutation or repair.

Current WU-04 implementation/qualification state in PR #8:

```text
IMPLEMENTED_AND_WORDPRESS_INTEGRATION_QUALIFIED_ON_PINNED_TARGET_TUPLE
AUTOMATED_QUALIFICATION_LAB_FIRST_FUNCTIONAL_SLICE
BROWSER_EVIDENCE_NOT_RUN
PRODUCTION_QUALIFICATION_NOT_PROVEN
```

Final exact-head evidence for PR #8 is bound to `2ea6e8a67e067047ea2a769ee35e3767d14edfcd`. On that exact head, WU-01 run `35585961455`, WU-02 run `35585961475`, WU-03 run `35585961432`, and WU-04 run `35585961489` all completed successfully. The WU-04 machine-readable artifact is `wu04-diagnostics-drift-35585961489-1` (artifact `10632141277`, digest `sha256:e78774e5ef5b2665540457a14545278e2fd34c825d826f644a9d4f3979209e2c`).

On WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5`, the dedicated WU-04 lab exercises deterministic fixtures for:

- `CANONICAL`;
- `WRONG_PAGE_ASSIGNMENT`;
- `PAGE_NOT_PUBLISHED`;
- missing/trashed/wrong-post-type forms of `PAGE_INVALID`;
- `CUSTOMIZED_DB_OVERRIDE`;
- `THEME_OVERRIDE`;
- `MISSING_TEMPLATE`;
- `UNKNOWN` fallback for a resolver result whose provenance does not match the proven plugin/theme/database contracts.

WordPress 7.1.1 source and runtime evidence establish the relevant resolution order as database template → active-theme file → registered plugin template. The detector classifies only provenance exposed by the resolved `WP_Block_Template`; it does not infer undocumented source semantics.

Where source and resolved markup are directly comparable, such as database or theme overrides, drift comparison normalizes block markup with `parse_blocks()` → `serialize_blocks()` before SHA-256 fingerprinting. The qualification fixture proves valid serialization whitespace compares equal while an added material block compares different. Registered plugin-template content is transformed by WordPress's native Block Hooks resolution path before it is returned, so WU-04 classifies the canonical plugin provider from proven `source` / `origin` / `plugin` provenance and deliberately leaves raw-source-versus-resolved content equivalence `NOT_PROVEN` instead of depending on private Core APIs or reporting a false mismatch.

For every WU-04 fixture, the lab snapshots relevant persistent state before/after inspection and real settings-page rendering / `Check Again`. PASS requires configuration, page-template assignment, page content, and any DB/theme override fixture to remain unchanged. Missing-template diagnostics also prove that inspection does not silently re-register the removed fixture template.

The same settings screen presents Persian-first practical status and next action, with optional technical details and a privacy-minimized read-only report. No CSS, JavaScript, automatic repair, browser automation, or persistent diagnostic cache is introduced by WU-04.

WU-04 evidence proves WordPress/runtime-state diagnostics on the disposable exact-version tuple. It does not prove Full Width browser geometry, browser RTL/accessibility/security behavior, Owner browser E2E/comprehension, direct production-host behavior, or production qualification.

## WU-05 — Full Width geometry proof

Start with Mother Architecture hypothesis `H1`.

Validate on the real frontend:

```text
320px
390px
430px
1440px
RTL
```

Required proof includes:

- safe mobile gutters;
- no unwanted TT25 narrow constraint on the SRWF application region;
- header/footer/navigation remain correct;
- Gravity Forms + Orbital + GTB behavior remains intact when those real dependencies are lawfully/reliably available in the lab;
- approved frontend typography remains effective when the corresponding real typography stack is present.

Prefer real browser measurements for geometry/overflow assertions, with screenshots as supporting evidence rather than the sole PASS criterion.

If a required real dependency is unavailable in CI, preserve that regression claim as `NOT_PROVEN` or `ENVIRONMENT_UNAVAILABLE` until stronger evidence exists.

If H1 fails, replace the implementation mechanism without reopening the architecture contract.

## WU-06 — Admin UX and security qualification

Exercise:

- first run;
- valid page;
- missing page;
- trashed page;
- non-published page;
- wrong type;
- insufficient capability;
- target page edit denial;
- nonce failure;
- successful apply;
- read-only Check Again;
- unknown/drift state;
- Persian RTL;
- technical LTR isolation;
- keyboard/focus;
- narrow admin layout;
- diagnostic privacy.

Automate the repeatable part through integration plus real browser/HTTP tests. Automated accessibility scanning is useful evidence for machine-detectable failures but does not, by itself, prove complete WCAG 2.2 AA conformance.

## WU-07 — E2E / comprehension / release gate

Run the real Owner path end to end.

Record:

- browser/runtime evidence;
- independent comprehension review where practical;
- Owner walkthrough;
- exact-target qualification state for every required release gate item.

The mechanical Owner journey should be browser-automated where practical, including navigation to the settings surface, first-run guidance presence, page selection, explicit apply, truthful result, frontend opening, and read-only verification when implemented.

Human comprehension remains a human evidence requirement. Production-host-specific facts not faithfully reproduced in the lab remain separate confirmation requirements.

Only when all applicable required gates pass may the build be called:

```text
PRODUCTION_QUALIFIED_FOR_SRWF
```

## Change-control rule

If any WU discovers that the frozen architecture cannot be implemented safely:

- stop the affected work;
- preserve the evidence;
- mark the affected claim `NOT_PROVEN`;
- propose the smallest architecture amendment;
- do not silently redesign the project inside an implementation PR.
