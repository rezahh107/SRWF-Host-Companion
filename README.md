# SRWF Host Companion

Project-specific WordPress host integration layer for SRWF: deterministic block templates, full-width shells, runtime/template governance, diagnostics, and bounded host-level integrations.

> **Current status:** V0 architecture remains frozen and approved, with the Owner-approved Inbox Full Width amendment implemented and published. WU-01 through WU-07 remain qualified to their documented claim ceilings on the pinned WordPress `7.1.1` / PHP `8.3.33` / Twenty Twenty-Five `1.5` tuple. Personal GitHub Release `v0.2.0` is published under `GPL-2.0-or-later`, with release minimums WordPress `7.1` and PHP `8.3`; it adds one independently selected Inbox Page using the same existing Full Width host canvas and does not add an Operational template, Gravity Flow behavior changes, Inbox presentation ownership, or GPP changes. Human comprehension, production-host confirmation, real Gravity Flow Inbox integration, GPP integration, unavailable real dependency regressions, complete WCAG 2.2 AA conformance, and `PRODUCTION_QUALIFIED_FOR_SRWF` remain unproven.

## Purpose

SRWF Host Companion owns the small WordPress **host-integration** boundary for SRWF.

Its original V0 product goal was:

> Let a non-technical administrator choose the SRWF Registration page and explicitly apply a canonical Full Width WordPress Block Template without editing theme files or learning Site Editor internals.

The post-`v0.1.0` Inbox amendment adds one equally bounded host-level need:

> Let the Owner independently select one Inbox Page and explicitly apply the **same** proven Full Width SRWF host canvas.

The plugin complements the host theme. It does not replace the theme, Gravity Forms, Gravity Flow, GTB, GPP, PersianGravity, or Vazir.

## Ownership boundary

```text
Twenty Twenty-Five
└── general site shell / header / footer / navigation

SRWF Host Companion
├── Owner-facing host setup
├── SRWF page-role mapping (registration + inbox)
├── canonical host template reuse
├── safe explicit template assignment
└── Registration-focused runtime / drift diagnostics

Gravity Forms + GTB
└── Registration behavior + presentation

Gravity Flow + GPP
└── workflow behavior + operational presentation

PersianGravity
└── admitted Persian Gravity capabilities

Vazir / Vazirmatn
└── approved frontend typography
```

For Inbox, SRWF Host Companion owns only WordPress Page selection/configuration and explicit reuse of the existing Full Width canvas. It does not own Gravity Flow Inbox content/behavior, workflow state, assignments/permissions, or GPP presentation.

## V0 scope and bounded post-v0.1.0 amendment

The frozen V0 scope remains historical and intentionally narrow:

- one Owner-facing admin surface under `Settings → SRWF Host`;
- one versioned `registration.page_id` role;
- one canonical template: `SRWF — Registration Full Width`;
- explicit save/apply behavior;
- read-only verification and drift diagnostics;
- Persian-first, translatable, RTL-safe admin UX;
- real browser/runtime qualification before any production-qualified claim.

A separate Operational template was not part of V0 and is still **not authorized**.

The Owner-approved [`INBOX_FULL_WIDTH_HOST_CANVAS_AMENDMENT.md`](docs/architecture/INBOX_FULL_WIDTH_HOST_CANVAS_AMENDMENT.md) now admits exactly one additional role, `roles.inbox.page_id`, for host-canvas assignment only. Inbox reuses the current `registration-full-width` template identity and `templates/registration-full-width.html`; no second Inbox/Operational template is introduced.

## Governing documents

Read these before implementation or technical review:

1. [`docs/architecture/MOTHER_ARCHITECTURE.md`](docs/architecture/MOTHER_ARCHITECTURE.md) — canonical frozen V0 product architecture, ownership boundaries, and stronger Production Qualification claim.
2. [`docs/architecture/INBOX_FULL_WIDTH_HOST_CANVAS_AMENDMENT.md`](docs/architecture/INBOX_FULL_WIDTH_HOST_CANVAS_AMENDMENT.md) — Owner-approved post-`v0.1.0` amendment admitting one independently selected Inbox Page for the existing Full Width host canvas only.
3. [`docs/architecture/AUTOMATED_QUALIFICATION_LAB.md`](docs/architecture/AUTOMATED_QUALIFICATION_LAB.md) — Owner-approved qualification-evidence amendment for repeatable CI/browser evidence.
4. [`docs/architecture/PERSONAL_GITHUB_RELEASE_POLICY.md`](docs/architecture/PERSONAL_GITHUB_RELEASE_POLICY.md) — Owner-approved authority for personal GitHub distribution, release identity/license/runtime minimums, release notes, and the distinction between personal-release readiness and `PRODUCTION_QUALIFIED_FOR_SRWF`.
5. [`AGENTS.md`](AGENTS.md) — operating contract for coding agents and automated contributors.
6. [`docs/architecture/PPDM_ADOPTION.md`](docs/architecture/PPDM_ADOPTION.md) — selectively adopted WordPress/self-guided UX guidance.
7. [`docs/implementation/V0_IMPLEMENTATION_PLAN.md`](docs/implementation/V0_IMPLEMENTATION_PLAN.md) — bounded V0 execution sequence and historical work-unit status.
8. [`docs/implementation/WU01_RUNTIME_FACTS.md`](docs/implementation/WU01_RUNTIME_FACTS.md) — target-runtime evidence and page-template contract.

The Mother Architecture remains frozen. Explicit Owner-approved amendments may add bounded post-V0 needs while preserving the product mission and ownership boundaries.

## Platform policy

Current release and evidence baseline:

```text
Personal GitHub release identity: v0.2.0
Release minimum WordPress: 7.1
Release minimum PHP: 8.3
Selected license: GPL-2.0-or-later

Observed production WordPress: 7.1.1
Observed production PHP: 8.3.33
Observed production TT25: 1.5

WU-01/WU-02/WU-03/WU-04/WU-05/WU-06/WU-07 pinned qualified lab WordPress: 7.1.1
WU-01/WU-02/WU-03/WU-04/WU-05/WU-06/WU-07 pinned qualified lab PHP: 8.3.33
Initial and qualified host theme: Twenty Twenty-Five 1.5
```

The release minimums are the Owner-selected metadata floor for the current personal GitHub `v0.2.0` release. They do not advertise broad compatibility beyond the exercised pinned tuple. Observed production identity provenance and disposable runtime/browser behavior are distinct evidence classes and do not by themselves establish production-host confirmation.

## Repository status

```text
Architecture: APPROVED / FROZEN FOR V0 + OWNER-APPROVED BOUNDED AMENDMENTS
Inbox Full Width host-canvas amendment: APPROVED / IMPLEMENTED / PUBLISHED IN v0.2.0
Personal GitHub release policy: APPROVED / OWNER_LOCKED
Release identity: v0.2.0
License: GPL-2.0-or-later
Release minimum WordPress/PHP: 7.1 / 8.3
WU-01 runtime-fact prerequisite: COMPLETE_FOR_WU02
WU-02 minimal runtime core: IMPLEMENTED / INTEGRATION_PROVEN
Configuration schema v2: IMPLEMENTED / PUBLISHED — registration + inbox; valid v1 remains readable without write-on-read migration
Registration template registration: IMPLEMENTED
Exact page-template assignment adapter: IMPLEMENTED / REUSED FOR INBOX
WU-03 Owner settings workflow: IMPLEMENTED / WORDPRESS_INTEGRATION_PROVEN; INBOX EXTENSION QUALIFIED
WU-04 runtime diagnostics/drift: IMPLEMENTED / REGISTRATION-FOCUSED / WORDPRESS_INTEGRATION_QUALIFIED_ON_PINNED_TUPLE
WU-05 Full Width geometry: IMPLEMENTED / REGISTRATION + INBOX HOST-CANVAS REUSE QUALIFIED
WU-06 admin UX/security/RTL/accessibility qualification: IMPLEMENTED / ADMIN_BROWSER_QUALIFICATION_PASS_ON_PINNED_TARGET_TUPLE
WU-07 mechanical Owner E2E: IMPLEMENTED / WU07_MECHANICAL_OWNER_E2E_PASS_ON_PINNED_TARGET_TUPLE
Real Gravity Flow Inbox integration: NOT_PROVEN
GPP integration: NOT_PROVEN
Human comprehension: NOT_PROVEN
Production-host confirmation: NOT_PROVEN
Complete WCAG 2.2 AA conformance: NOT_PROVEN
Unavailable real dependency regressions: ENVIRONMENT_UNAVAILABLE / NOT_PROVEN
PRODUCTION_QUALIFIED_FOR_SRWF: NOT_PROVEN
Personal GitHub release v0.1.0: PUBLISHED
```

The unreleased configuration model keeps the single option `srwf_host_companion_config`. Reads normalize valid schema v1 in memory as Registration plus unconfigured Inbox (`0`) without writing. Registration-only legacy persistence may remain schema v1 until Inbox is explicitly configured. The first explicit Inbox persistence upgrades storage to schema v2 and preserves Registration. Once schema v2 exists, either role setter preserves the other role. Malformed/unsupported state remains fail-closed/unconfigured.

WU-03 provides the real Owner-facing mutation workflow under `Settings → SRWF Host`. Registration keeps its existing selector/action. The unreleased Inbox extension adds an independent `صفحه اینباکس` selector and independent explicit apply operation on the same screen. Both routes retain `manage_options`, role-specific nonce validation, target `edit_post` authorization, WordPress Page validation, canonical assignment/readback, page-change safety, truthful bounded result messages, and no page-content mutation. Applying one role does not re-save or re-assign the other role. No admin or frontend JavaScript/CSS is introduced.

WU-04 remains Registration-focused. It extends the same single screen with read-only current-state diagnostics and `بررسی دوباره`. Diagnostics reuse the existing page-validity model and separately record page evidence, page-template assignment, active frontend-provider evidence, raw-source comparison evidence, transformed returned-template evidence, interpretation, and the practical next action. Opening/rendering the screen and `بررسی دوباره` do not save configuration, assign a template, rewrite page content, delete a `wp_template`, rewrite a theme file, or repair drift. Inbox-specific diagnostics are not part of the current amendment.

On WordPress `7.1.1`, provider truth follows the same exact-slug `get_block_templates()` published-candidate model used by frontend `resolve_block_template()`. A matching draft or trashed database `wp_template` is therefore not promoted to `CUSTOMIZED_DB_OVERRIDE`; resolution falls through to the eligible theme/plugin provider. A published matching database override remains eligible and wins according to Core precedence. Provider classification continues to come only from proven `WP_Block_Template` provenance; unsupported provenance stays `UNKNOWN`.

Content drift is a separate evidence problem. WU-04 compares repository canonical source only against corresponding raw, pre-Block-Hooks provider source: persisted raw `wp_template` `post_content` for a proven published DB provider, or the real child/parent theme template file located through public WordPress theme/folder APIs for a proven theme provider. Comparable raw markup is normalized with `parse_blocks()` → `serialize_blocks()` before SHA-256 fingerprinting. The returned `WP_Block_Template->content` is retained only as transformed runtime evidence and is never used as raw-source equality truth.

The WU-04 lab contains native Block Hooks falsification: raw DB and theme sources that are canonically equivalent still compare equal even when Core transforms returned template content, while separate materially changed raw DB/theme fixtures still compare different. Draft and trashed database-provider fixtures prove those objects are not frontend-eligible active providers; a published DB fixture proves the positive provider control. If exact raw source cannot be established, source comparison is explicitly `NOT_PROVEN` with `content_matches_canonical = null` rather than manufacturing equality or drift.

Registered plugin provider identity remains proven from `source=plugin`, `origin=plugin`, and plugin identity. Direct raw-source-versus-post-resolution plugin content equivalence remains `NOT_PROVEN_NATIVE_BLOCK_HOOKS_TRANSFORM` because WordPress applies native Block Hooks during resolution.

Every WU-04 fixture snapshots relevant persistent state before/after diagnostics and verifies no hidden repair. A privacy-minimized text report is available through native read-only admin markup without JavaScript; it excludes page/form content, student data, uploads, authentication material, nonces, cookies and credentials.

WU-05 keeps Full Width ownership at the WordPress host-template layer. The canonical template uses native block-layout primitives only: an `alignfull` shell, template-local constrained layout sizing with `contentSize` / `wideSize` at `100%`, an aligned Post Content boundary, and TT25 spacing tokens for safe horizontal gutters. It does not mutate global TT25 `contentSize`, introduce a page builder, add frontend JavaScript, add generic form styling, or require a viewport-breakout hack.

The WU-05 real-browser lab runs Chromium through Playwright against the exact disposable target tuple in Persian RTL at `320`, `390`, `430`, and `1440` CSS px. For this amendment the same lab creates independent synthetic Registration and Inbox WordPress Pages, assigns the same canonical template through the same adapter, and requires both roles to pass the Full Width host-canvas geometry/overflow/header/footer/navigation checks before the workflow can report its existing qualified status. This synthetic Inbox evidence is deliberately limited to the WordPress host canvas.

Successful synthetic host-geometry evidence does **not** prove real Gravity Flow Inbox integration, GPP integration, or unavailable real Gravity Forms/Orbital/GTB/approved Vazir/Vazirmatn integration. Those dependency-backed claims remain `ENVIRONMENT_UNAVAILABLE / NOT_PROVEN` or `NOT_PROVEN` as applicable.

WU-06 reuses the existing WU-05 Playwright/Chromium foundation for real wp-admin qualification rather than introducing a second browser platform. On the pinned WordPress `7.1.1` / PHP `8.3.33` / Twenty Twenty-Five `1.5` tuple in Persian RTL, the lab exercises first-run, valid published/non-published pages, missing/trashed/wrong-type pages, wrong assignment, canonical state, published DB override, theme override, missing template and `UNKNOWN`. The browser qualification also exercises keyboard/focus, native disclosure, a `390×900` narrow-admin layout, authorization boundaries, invalid/missing nonce rejection, successful explicit Registration apply, read-only `بررسی دوباره`, diagnostic privacy, and a scoped automated accessibility scan. Its Registration path remains deterministic with the independent Inbox control present.

WU-07 reuses that same browser foundation for the mechanical Owner Registration journey: login, reach `Settings → SRWF Host`, observe first-run guidance, select a Registration page, explicitly save/apply, observe the truthful result, verify persisted Registration configuration and canonical assignment, open the frontend Registration page, verify the canonical host shell/RTL/basic host integrity without horizontal overflow, return to settings, and run read-only `Check Again`. This establishes `WU07_MECHANICAL_OWNER_E2E_PASS_ON_PINNED_TARGET_TUPLE` only; it is not an Inbox/Gravity Flow E2E claim.

The Owner-approved Personal GitHub Release Policy permitted the already-published personal/project-specific `v0.1.0` GitHub release without converting unavailable evidence into PASS. This unreleased Inbox capability does not alter that historical release, does not change `.github/release-manifest.json`, and does not publish or imply `v0.2.0`. Human comprehension, direct production-host confirmation, complete WCAG 2.2 AA conformance, real Gravity Flow Inbox/GPP integration, and unavailable dependency regressions remain unproven, and the source must not be labeled `PRODUCTION_QUALIFIED_FOR_SRWF` on that basis.

Exact-head workflow/run/artifact identities belong in focused PR/release evidence rather than durable current-state text that would become stale when lifecycle state changes.

The approved Automated Qualification Lab extends the existing disposable runtime-lab through WU-07 and bounded later host-canvas qualification where behavior can be reproduced honestly. It favors deterministic fixtures, real-browser assertions when needed, machine-readable evidence and useful failure artifacts. Human comprehension and irreducibly production-specific confirmation remain separate evidence classes, and unavailable real dependencies must not be represented by synthetic PASS claims.

## Repository layout

```text
.
├── .github/
│   ├── workflows/
│   │   ├── wu01-runtime-facts.yml
│   │   ├── wu02-runtime-core.yml
│   │   ├── wu03-owner-settings.yml
│   │   ├── wu04-diagnostics-drift.yml
│   │   ├── wu05-full-width-geometry.yml
│   │   ├── wu06-admin-qualification.yml
│   │   └── wu07-owner-e2e.yml
│   └── PULL_REQUEST_TEMPLATE.md
├── docs/
│   ├── architecture/
│   │   ├── MOTHER_ARCHITECTURE.md
│   │   ├── INBOX_FULL_WIDTH_HOST_CANVAS_AMENDMENT.md
│   │   ├── AUTOMATED_QUALIFICATION_LAB.md
│   │   ├── PERSONAL_GITHUB_RELEASE_POLICY.md
│   │   └── PPDM_ADOPTION.md
│   ├── evidence/
│   │   └── PRODUCTION_SITE_HEALTH_IDENTITY.md
│   └── implementation/
│       ├── V0_IMPLEMENTATION_PLAN.md
│       └── WU01_RUNTIME_FACTS.md
├── src/
│   ├── AdminSettings.php
│   ├── Configuration.php
│   ├── PageTemplateAssignment.php
│   ├── TemplateDiagnostics.php
│   └── TemplateRegistrar.php
├── templates/
│   └── registration-full-width.html
├── tests/
│   ├── browser/
│   │   ├── wu05-full-width.mjs
│   │   ├── wu06-admin-qualification.mjs
│   │   └── wu07-owner-e2e.mjs
│   ├── release/
│   │   └── verify-release-contract.py
│   └── runtime-lab/
│       ├── fixture-plugin/
│       │   └── srwf-host-companion-wu06-fixture.php
│       ├── wu02-runtime-core.php
│       ├── wu03-owner-settings.php
│       ├── wu05-full-width.php
│       └── wu06-admin-fixtures.php
├── AGENTS.md
├── CHANGELOG.md
├── LICENSE
├── SECURITY.md
├── README.md
└── srwf-host-companion.php
```

Do not add placeholder abstractions or empty directories. Add structure only when it contains real behavior.

## Development workflow

The initial repository bootstrap may land directly on `main`. After bootstrap, material work should use focused branches and pull requests.

Recommended branch prefixes:

```text
feat/
fix/
docs/
test/
chore/
```

Each material PR must state its scope, governing architecture/amendment, what behavior remains unchanged, tests actually run, unproven/runtime-sensitive checks, and release impact.

## Evidence discipline

Use explicit states such as:

```text
PASS
FAIL
NOT_RUN
NOT_PROVEN
ENVIRONMENT_UNAVAILABLE
```

A unit test does not prove browser behavior. Source inspection does not prove production runtime. A fixture that creates prerequisite state does not prove the Owner can reach that state through the real product path. Automated accessibility scanning does not, by itself, prove full WCAG 2.2 AA conformance. Disposable exact-version CI does not, by itself, prove the real production host. Mechanical browser E2E does not prove human comprehension. Synthetic Inbox host-canvas geometry does not prove Gravity Flow Inbox or GPP integration.

## License and personal distribution

The Owner selected `GPL-2.0-or-later`. The repository `LICENSE` contains the GNU GPL version 2 license text, and the plugin header carries the matching `GPL-2.0-or-later` declaration.

Personal GitHub distribution, release identity, release runtime minimums, release-note claim ceilings, and personal-release readiness are governed by [`docs/architecture/PERSONAL_GITHUB_RELEASE_POLICY.md`](docs/architecture/PERSONAL_GITHUB_RELEASE_POLICY.md).

This personal release policy does not weaken the stronger `PRODUCTION_QUALIFIED_FOR_SRWF` evidence boundary.
