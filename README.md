# SRWF Host Companion

Project-specific WordPress host integration layer for SRWF: deterministic block templates, full-width shells, runtime/template governance, diagnostics, and future bounded host-level integrations.

> **Current status:** V0 architecture is frozen and approved. WU-01 target-runtime facts, WU-02 minimal runtime core, WU-03 Owner settings workflow, and WU-04 read-only diagnostics/drift are implemented on `main`. WU-05 Full Width host geometry is implemented in PR #9 and real-browser automated-qualified on the pinned WordPress `7.1.1` / PHP `8.3.33` / Twenty Twenty-Five `1.5` tuple. WU-06 admin/browser qualification, WU-07 browser E2E/comprehension/release gate, real unavailable dependency regressions, and production qualification remain open.

## Purpose

SRWF Host Companion owns the small WordPress **host-integration** boundary for SRWF.

Its first product goal is simple:

> Let a non-technical administrator choose the SRWF Registration page and explicitly apply a canonical Full Width WordPress Block Template without editing theme files or learning Site Editor internals.

The plugin complements the host theme. It does not replace the theme, Gravity Forms, Gravity Flow, GTB, GPP, PersianGravity, or Vazir.

## Ownership boundary

```text
Twenty Twenty-Five
└── general site shell / header / footer / navigation

SRWF Host Companion
├── Owner-facing host setup
├── SRWF page-role mapping
├── canonical host templates
├── safe explicit template assignment
└── runtime / drift diagnostics

Gravity Forms + GTB
└── Registration behavior + presentation

Gravity Flow + GPP
└── workflow behavior + operational presentation

PersianGravity
└── admitted Persian Gravity capabilities

Vazir / Vazirmatn
└── approved frontend typography
```

## V0 scope

V0 is intentionally narrow:

- one Owner-facing admin surface under `Settings → SRWF Host`;
- one versioned `registration.page_id` role;
- one canonical template: `SRWF — Registration Full Width`;
- explicit save/apply behavior;
- read-only verification and drift diagnostics;
- Persian-first, translatable, RTL-safe admin UX;
- real browser/runtime qualification before any production-qualified claim.

A separate Operational template is **not** part of V0. It may be added only if Inbox / Entry Detail runtime evidence proves a distinct shell is needed.

## Governing documents

Read these before implementation or technical review:

1. [`docs/architecture/MOTHER_ARCHITECTURE.md`](docs/architecture/MOTHER_ARCHITECTURE.md) — canonical V0 architecture and invariants.
2. [`docs/architecture/AUTOMATED_QUALIFICATION_LAB.md`](docs/architecture/AUTOMATED_QUALIFICATION_LAB.md) — Owner-approved qualification-evidence amendment; extends the Mother Architecture without replacing the Release Gate.
3. [`AGENTS.md`](AGENTS.md) — operating contract for coding agents and automated contributors.
4. [`docs/architecture/PPDM_ADOPTION.md`](docs/architecture/PPDM_ADOPTION.md) — selectively adopted WordPress/self-guided UX guidance.
5. [`docs/implementation/V0_IMPLEMENTATION_PLAN.md`](docs/implementation/V0_IMPLEMENTATION_PLAN.md) — bounded execution sequence and current work-unit status.
6. [`docs/implementation/WU01_RUNTIME_FACTS.md`](docs/implementation/WU01_RUNTIME_FACTS.md) — target-runtime evidence and page-template contract.

The Mother Architecture is frozen for V0. Owner-approved architecture amendments may extend implementation/evidence policy while preserving the frozen V0 product mission, boundaries and Release Gate.

## Platform policy

Current evidence baseline:

```text
Minimum WordPress: 6.7
Observed production WordPress: 7.1.1
WU-01/WU-02/WU-03/WU-04/WU-05 pinned qualified lab WordPress: 7.1.1

Preferred engineering PHP floor: 8.2+
Observed production PHP: 8.3.33
WU-01/WU-02/WU-03/WU-04/WU-05 pinned qualified lab PHP: 8.3.33
Minimum production PHP support policy: NOT_YET_FROZEN

Initial host theme: Twenty Twenty-Five
Observed production TT25: 1.5
WU-01/WU-02/WU-03/WU-04/WU-05 pinned qualified lab TT25: 1.5
```

Observed production identity provenance and disposable runtime behavior are distinct evidence classes. They do not by themselves establish production qualification.

## Repository status

```text
Architecture: APPROVED / FROZEN FOR V0 + OWNER-APPROVED QUALIFICATION-LAB AMENDMENT
WU-01 runtime-fact prerequisite: COMPLETE_FOR_WU02
WU-02 minimal runtime core: IMPLEMENTED_ON_MAIN
Configuration schema v1: IMPLEMENTED
Registration template registration: IMPLEMENTED
Exact page-template assignment adapter: IMPLEMENTED
WU-03 Owner settings workflow: IMPLEMENTED_ON_MAIN / WORDPRESS_INTEGRATION_PROVEN
WU-04 runtime diagnostics/drift: IMPLEMENTED_ON_MAIN / WORDPRESS_INTEGRATION_QUALIFIED_ON_PINNED_TUPLE
WU-05 Full Width geometry: IMPLEMENTED_IN_PR_9 / FULL_WIDTH_GEOMETRY_AUTOMATED_QUALIFIED_ON_PINNED_TARGET_TUPLE
WU-06 admin UX/security/RTL/accessibility qualification: NOT_RUN
WU-07 browser/E2E release gate: NOT_RUN
Automated Qualification Lab: WU04_INTEGRATION + WU05_REAL_BROWSER_FOUNDATION
Production qualification: NOT_PROVEN
Production release: NOT_PUBLISHED
```

WU-03 provides the real Owner-facing mutation workflow under `Settings → SRWF Host`: Persian-first first-run guidance, one WordPress-page selector backed only by schema-v1 `roles.registration.page_id`, the explicit `ذخیره و اعمال قالب تمام‌عرض` action, page-state classification, capability/nonce guards, canonical assignment/readback, page-change safety, and truthful bounded result messages. It introduces no admin or frontend JavaScript/CSS.

WU-04 extends that same single screen with read-only current-state diagnostics and `بررسی دوباره`. Diagnostics reuse the existing page-validity model and separately record page evidence, page-template assignment, active frontend-provider evidence, raw-source comparison evidence, transformed returned-template evidence, interpretation, and the practical next action. Opening/rendering the screen and `بررسی دوباره` do not save configuration, assign a template, rewrite page content, delete a `wp_template`, rewrite a theme file, or repair drift.

On WordPress `7.1.1`, provider truth follows the same exact-slug `get_block_templates()` published-candidate model used by frontend `resolve_block_template()`. A matching draft or trashed database `wp_template` is therefore not promoted to `CUSTOMIZED_DB_OVERRIDE`; resolution falls through to the eligible theme/plugin provider. A published matching database override remains eligible and wins according to Core precedence. Provider classification continues to come only from proven `WP_Block_Template` provenance; unsupported provenance stays `UNKNOWN`.

Content drift is a separate evidence problem. WU-04 compares repository canonical source only against corresponding raw, pre-Block-Hooks provider source: persisted raw `wp_template` `post_content` for a proven published DB provider, or the real child/parent theme template file located through public WordPress theme/folder APIs for a proven theme provider. Comparable raw markup is normalized with `parse_blocks()` → `serialize_blocks()` before SHA-256 fingerprinting. The returned `WP_Block_Template->content` is retained only as transformed runtime evidence and is never used as raw-source equality truth.

The WU-04 lab contains native Block Hooks falsification: raw DB and theme sources that are canonically equivalent still compare equal even when Core transforms returned template content, while separate materially changed raw DB/theme fixtures still compare different. Draft and trashed database-provider fixtures prove those objects are not frontend-eligible active providers; a published DB fixture proves the positive provider control. If exact raw source cannot be established, source comparison is explicitly `NOT_PROVEN` with `content_matches_canonical = null` rather than manufacturing equality or drift.

Registered plugin provider identity remains proven from `source=plugin`, `origin=plugin`, and plugin identity. Direct raw-source-versus-post-resolution plugin content equivalence remains `NOT_PROVEN_NATIVE_BLOCK_HOOKS_TRANSFORM` because WordPress applies native Block Hooks during resolution.

Every WU-04 fixture snapshots relevant persistent state before/after diagnostics and verifies no hidden repair. A privacy-minimized text report is available through native read-only admin markup without JavaScript; it excludes page/form content, student data, uploads, authentication material, nonces, cookies and credentials.

WU-05 keeps Full Width ownership at the WordPress host-template layer. The canonical Registration template uses native block-layout primitives only: an `alignfull` Registration shell, template-local constrained layout sizing with `contentSize` / `wideSize` at `100%`, an aligned Post Content boundary, and TT25 spacing tokens for safe horizontal gutters. It does not mutate global TT25 `contentSize`, introduce a page builder, add frontend JavaScript, add generic form styling, or require a viewport-breakout hack.

The WU-05 real-browser lab runs Chromium through Playwright against the exact disposable target tuple in Persian RTL at `320`, `390`, `430`, and `1440` CSS px. It records shell/content bounds, physical and inline gutters, rendered width, document/client overflow evidence, header/footer/navigation integrity, canonical template assignment/rendering, and dependency claim state in a machine-readable artifact. H1 is supported on the pinned tuple by measured browser geometry; screenshots remain diagnostic-only evidence.

The successful synthetic host-geometry evidence does **not** prove unavailable real Gravity Forms, Orbital, GTB, or approved Vazir/Vazirmatn integration. Those dependency-backed claims remain `ENVIRONMENT_UNAVAILABLE / NOT_PROVEN`. WU-06 browser admin RTL/accessibility/security qualification, WU-07 Owner browser E2E/comprehension, production-host confirmation, complete WCAG 2.2 AA conformance, and production qualification also remain outside the WU-05 claim ceiling.

Exact-head workflow/run/artifact identity for the open WU-05 PR is recorded in PR evidence rather than embedded as a self-referential commit identifier here.

The approved Automated Qualification Lab extends the existing disposable runtime-lab for WU-04 through WU-07 where behavior can be reproduced honestly. It favors deterministic fixtures, real-browser assertions when needed, machine-readable evidence and useful failure artifacts. Human comprehension and irreducibly production-specific confirmation remain separate evidence requirements, and unavailable real dependencies must not be represented by synthetic PASS claims.

## Repository layout

```text
.
├── .github/
│   ├── workflows/
│   │   ├── wu01-runtime-facts.yml
│   │   ├── wu02-runtime-core.yml
│   │   ├── wu03-owner-settings.yml
│   │   ├── wu04-diagnostics-drift.yml
│   │   └── wu05-full-width-geometry.yml
│   └── PULL_REQUEST_TEMPLATE.md
├── docs/
│   ├── architecture/
│   │   ├── MOTHER_ARCHITECTURE.md
│   │   ├── AUTOMATED_QUALIFICATION_LAB.md
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
│   │   └── wu05-full-width.mjs
│   └── runtime-lab/
│       └── wu05-full-width.php
├── AGENTS.md
├── CHANGELOG.md
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

Each material PR must state its scope, governing architecture, what behavior remains unchanged, tests actually run, unproven/runtime-sensitive checks, and release impact.

## Evidence discipline

Use explicit states such as:

```text
PASS
FAIL
NOT_RUN
NOT_PROVEN
ENVIRONMENT_UNAVAILABLE
```

A unit test does not prove browser behavior. Source inspection does not prove production runtime. A fixture that creates prerequisite state does not prove the Owner can reach that state through the real product path. Automated accessibility scanning does not, by itself, prove full WCAG 2.2 AA conformance. Disposable exact-version CI does not, by itself, prove the real production host.

## License

A repository license has **not yet been selected**.

Do not publish or describe a production distribution as licensed for general use until the Owner explicitly chooses and records a license.
