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

WU-04 established the first deterministic integration slice. WU-05 added the first reusable frontend real-browser slice, and WU-06 reuses that same Playwright/Chromium foundation for real wp-admin qualification without coupling the product runtime to the browser framework.

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

Current WU-04 implementation/qualification state on `main` after merged PR #8:

```text
IMPLEMENTED_AND_WORDPRESS_INTEGRATION_QUALIFIED_ON_PINNED_TARGET_TUPLE
AUTOMATED_QUALIFICATION_LAB_FIRST_FUNCTIONAL_SLICE
BROWSER_EVIDENCE_NOT_RUN_FOR_WU04
PRODUCTION_QUALIFICATION_NOT_PROVEN
```

Exact-head workflow/run/artifact identity is recorded in focused PR evidence rather than embedded as a self-referential commit identifier in this plan.

On WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5`, the dedicated WU-04 lab exercises deterministic fixtures for:

- `CANONICAL`;
- `WRONG_PAGE_ASSIGNMENT`;
- `PAGE_NOT_PUBLISHED`;
- missing/trashed/wrong-post-type forms of `PAGE_INVALID`;
- frontend exclusion of matching draft and trashed database `wp_template` objects;
- positive selection of a matching published `CUSTOMIZED_DB_OVERRIDE`;
- `CUSTOMIZED_DB_OVERRIDE` under native Block Hooks with canonically equivalent and materially different raw source;
- `THEME_OVERRIDE` under native Block Hooks with canonically equivalent and materially different raw source;
- `MISSING_TEMPLATE`;
- `UNKNOWN` through the repaired `get_block_templates()` observation seam.

WordPress 7.1.1 source and runtime evidence establish two distinct WU-04 evidence boundaries.

**Active provider evidence** follows the same public published-candidate model used by frontend resolution: exact-slug `get_block_templates()` candidates. Its normal non-`wp_id` database query admits only published `wp_template` objects; matching draft or trashed DB objects therefore do not become active frontend providers and resolution falls through to the eligible theme or registered-plugin provider. A published matching DB object remains eligible and retains Core precedence. Provider classification is derived only from the resulting `WP_Block_Template` provenance; unsupported provenance remains `UNKNOWN`.

**Content drift evidence** comes from raw provider source before Core's Block Hooks transformations. For a proven active DB provider, WU-04 reads the persisted `wp_template` source associated with the proven published `wp_id`. For a proven theme provider, it locates the actual child/parent theme template file through WordPress theme and block-theme-folder APIs and reads that raw file. Comparable raw markup is normalized with `parse_blocks()` → `serialize_blocks()` before SHA-256 fingerprinting. If exact raw source cannot be established, comparison remains `NOT_PROVEN` with `content_matches_canonical = null`; it is never promoted to equality or mismatch from transformed returned content.

The returned `WP_Block_Template->content` is retained only as transformed runtime evidence. Dedicated native Block Hooks falsification fixtures prove that canonically equivalent raw DB and theme source remains a source-level match even when Core changes returned template content, while separate materially changed DB/theme raw-source positive controls still produce a mismatch. This prevents both the original false-mismatch defect and a fix that merely suppresses all drift comparisons.

Registered-plugin provider identity remains proven from WordPress `source` / `origin` / `plugin` provenance. Direct repository-raw-source versus post-resolution plugin content equivalence remains `NOT_PROVEN_NATIVE_BLOCK_HOOKS_TRANSFORM`; product code does not call private Core helpers to manufacture that comparison.

For every WU-04 fixture, the lab snapshots relevant persistent state before/after inspection and real settings-page rendering / `Check Again`. PASS requires configuration, page-template assignment, page content, and DB/theme override source sentinels to remain unchanged. Missing-template diagnostics also prove that inspection does not silently re-register the removed fixture template.

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

Current WU-05 implementation/qualification state on `main` after merged PR #9:

```text
IMPLEMENTED_NATIVE_H1_ON_MAIN
FULL_WIDTH_GEOMETRY_AUTOMATED_QUALIFIED_ON_PINNED_TARGET_TUPLE
REAL_BROWSER_RTL_MATRIX_PASS
GRAVITY_FORMS_ORBITAL_GTB_TYPOGRAPHY_ENVIRONMENT_UNAVAILABLE_NOT_PROVEN
PRODUCTION_QUALIFICATION_NOT_PROVEN
```

The actual narrow constraint was confirmed at the host block-layout layer. Twenty Twenty-Five exposes a global article `contentSize` of `645px`; the canonical Registration template previously supplied a plain Group + Post Content composition with no local width override. WU-05 keeps the fix at the host-template boundary rather than moving responsibility into GTB/GPP or mutating TT25 globally.

The selected product mechanism is the native H1 route:

```text
Registration shell
→ alignfull
→ template-local constrained layout
→ contentSize = 100%
→ wideSize = 100%
→ TT25 spacing-token horizontal gutter

Post Content
→ alignfull
→ local contentSize = 100%
→ local wideSize = 100%
```

No product frontend CSS or JavaScript is required. No `100vw`, negative-margin breakout, Page Builder dependency, generic form-control styling, or global `theme.json` layout mutation is introduced.

The WU-05 lab reuses the exact disposable WordPress provisioning direction and adds pinned Playwright/Chromium only to the test boundary. Synthetic fixture setup persists schema-v1 Registration configuration and performs canonical template assignment/readback through the real product adapter before the browser opens the frontend.

For each required RTL viewport, the browser records viewport width, shell/Post Content/application bounds, physical and logical inline gutters, client/document/body widths, overflow state, header/footer/navigation integrity, and canonical template render evidence. The successful measured application widths are:

```text
320px viewport  → 260px application, 30px / 30px inline gutters
390px viewport  → 330px application, 30px / 30px inline gutters
430px viewport  → 370px application, 30px / 30px inline gutters
1440px viewport → 1340px application, 50px / 50px inline gutters
```

At all four widths, `scrollWidth == clientWidth`, direction is RTL, and header/footer/navigation are present. At `1440px`, the application region measures `1340px` while the global TT25 article `contentSize` remains `645px`, establishing the bounded Full Width host-geometry claim without global theme mutation.

H1 falsification was explicit rather than assumed. The first browser attempt exposed a 320px overflow, but the retained failure screenshot/JSON showed the shell, `30px` gutters, and host chrome were already correct; the overflow came from an unbreakable synthetic marker string in the fixture. After removing that fixture artifact, the same native H1 product mechanism passed all four required viewports. Therefore a scoped CSS fallback was not admitted.

The bounded machine-readable artifact records dependency availability separately. Gravity Forms, Orbital, GTB, and approved Vazir/Vazirmatn were unavailable in the exercised CI environment and remain `ENVIRONMENT_UNAVAILABLE / NOT_PROVEN`; synthetic equivalents are not used to manufacture regression PASS claims.

WU-05 establishes:

```text
FULL_WIDTH_GEOMETRY_AUTOMATED_QUALIFIED_ON_PINNED_TARGET_TUPLE
```

It does not establish WU-06, WU-07, Owner comprehension, production-host qualification, complete WCAG 2.2 AA conformance, or `PRODUCTION_QUALIFIED_FOR_SRWF`.

Exact final-head workflow/run/artifact identity is recorded in PR evidence rather than embedded as a self-referential identifier in this plan.

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

Current WU-06 implementation/qualification state in PR #10:

```text
ADMIN_BROWSER_QUALIFICATION_IMPLEMENTED
ADMIN_BROWSER_QUALIFICATION_PASS_ON_PINNED_TARGET_TUPLE
REAL_WP_ADMIN_PERSIAN_RTL_PASS
KEYBOARD_FOCUS_AND_NARROW_ADMIN_PASS
AUTHORIZATION_NONCE_TARGET_EDIT_BOUNDARIES_PASS
DIAGNOSTIC_PRIVACY_PASS
MACHINE_DETECTABLE_ACCESSIBILITY_CHECK_PASS
WU07_NOT_RUN
HUMAN_COMPREHENSION_NOT_RUN
PRODUCTION_HOST_QUALIFICATION_NOT_PROVEN
COMPLETE_WCAG_2_2_AA_CONFORMANCE_NOT_PROVEN
GRAVITY_FORMS_ORBITAL_GTB_VAZIR_ENVIRONMENT_UNAVAILABLE_NOT_PROVEN
```

WU-06 reuses the WU-05 pinned Playwright/Chromium browser foundation instead of creating a parallel browser stack. The disposable exact-target lab runs WordPress `7.1.1`, PHP `8.3.33`, Twenty Twenty-Five `1.5`, and Persian `fa_IR` wp-admin.

The deterministic state matrix exercises:

- first-run / unconfigured state;
- valid published and valid non-published pages;
- missing, trashed, and wrong-post-type configured targets;
- wrong page-template assignment;
- canonical active state;
- published database override;
- theme-file override;
- missing canonical template;
- unsupported provider provenance represented truthfully as `UNKNOWN`.

For each state, PASS requires the rendered Persian Owner-facing message to match the underlying WU-04 evidence model, the document/plugin surface to compute RTL, technical identifiers and the read-only diagnostic report to remain LTR/copyable, native semantic controls to remain present, and read-only browser rendering plus the automated accessibility scan to leave configuration/page/template/provider sentinels unchanged.

The browser qualification also exercises logical Tab/Shift+Tab progression, visible rendered focus, native `<details>/<summary>` keyboard disclosure, and the primary selector/apply path. The narrow-admin check uses a `390×900` viewport and requires document/body width containment, no visible plugin-owned element escaping the viewport, no Registration label/select overlap, and reachable controls/technical disclosure.

Security/authorization evidence uses real browser and authenticated HTTP requests. A user without `manage_options` is denied the protected settings and mutation boundaries; a user with `manage_options` but without target-page edit permission fails closed; invalid and missing nonces leave sentinels unchanged and do not present fake success; the authorized keyboard-driven path persists schema-v1 configuration and canonical assignment/readback without rewriting page content or the previously configured page; and keyboard-triggered `بررسی دوباره` is verified read-only.

The diagnostic privacy check injects synthetic student/form values, an upload URL, cookies, credentials/passwords, nonce/session-like material, secrets/tokens, and synthetic user email PII, then proves those markers are absent from the copyable report while bounded technical fields remain present.

The pinned `@axe-core/playwright` scan is scoped to the plugin `.wrap` and is recorded only as a machine-detectable accessibility check. A green scan does **not** establish complete WCAG 2.2 AA conformance or human comprehension.

WU-06 also dispatches and collects the existing WU-01 through WU-05 workflows on the exact tested ref before accepting the bounded result, preventing the new browser qualification from masking earlier work-unit regressions. Exact final-head workflow/run/artifact identity remains focused PR evidence rather than a self-referential identifier in this plan.

WU-06 establishes only the bounded disposable-CI admin/browser qualification. It does not establish WU-07, independent human comprehension, direct production-host behavior, unavailable real Gravity Forms/Orbital/GTB/Vazir integration, complete WCAG 2.2 AA conformance, or `PRODUCTION_QUALIFIED_FOR_SRWF`.

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