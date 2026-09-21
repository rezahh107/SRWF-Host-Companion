# SRWF Host Companion

Project-specific WordPress host integration layer for SRWF: deterministic block templates, full-width shells, runtime/template governance, diagnostics, and future bounded host-level integrations.

> **Current status:** V0 architecture is frozen and approved. WU-01 target-runtime facts, WU-02 minimal runtime core, and WU-03 Owner settings workflow are implemented on `main`. WU-04 read-only diagnostics/drift is implemented and exact-target qualified in open PR #8 through the first real Automated Qualification Lab fixture matrix. WU-05 Full Width browser geometry, WU-06 admin/browser qualification, WU-07 browser E2E/comprehension/release gate, and production qualification remain open.

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
WU-01/WU-02/WU-03/WU-04 pinned qualified lab WordPress: 7.1.1

Preferred engineering PHP floor: 8.2+
Observed production PHP: 8.3.33
WU-01/WU-02/WU-03/WU-04 pinned qualified lab PHP: 8.3.33
Minimum production PHP support policy: NOT_YET_FROZEN

Initial host theme: Twenty Twenty-Five
Observed production TT25: 1.5
WU-01/WU-02/WU-03/WU-04 pinned qualified lab TT25: 1.5
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
WU-04 runtime diagnostics/drift: IMPLEMENTED_IN_PR_8 / WORDPRESS_INTEGRATION_QUALIFIED_ON_PINNED_TUPLE
WU-05 Full Width geometry qualification: NOT_PROVEN
WU-06 admin UX/security/RTL/accessibility qualification: NOT_RUN
WU-07 browser/E2E release gate: NOT_RUN
Automated Qualification Lab: FIRST_FUNCTIONAL_SLICE_WU04_IMPLEMENTED_IN_PR_8
Production qualification: NOT_PROVEN
Production release: NOT_PUBLISHED
```

WU-03 provides the real Owner-facing mutation workflow under `Settings → SRWF Host`: Persian-first first-run guidance, one WordPress-page selector backed only by schema-v1 `roles.registration.page_id`, the explicit `ذخیره و اعمال قالب تمام‌عرض` action, page-state classification, capability/nonce guards, canonical assignment/readback, page-change safety, and truthful bounded result messages. It introduces no admin or frontend JavaScript/CSS.

WU-04 in PR #8 extends that same single screen with read-only current-state diagnostics and `بررسی دوباره`. Diagnostics reuse the existing page-validity model and separately record page evidence, page-template assignment, the template provider WordPress resolves, interpretation, and the practical next action. Opening/rendering the screen and `بررسی دوباره` do not save configuration, assign a template, rewrite page content, delete a `wp_template`, rewrite a theme file, or repair drift.

On the pinned WordPress `7.1.1` runtime, WU-04 distinguishes the proven resolution states `CANONICAL`, `CUSTOMIZED_DB_OVERRIDE`, `THEME_OVERRIDE`, `MISSING_TEMPLATE`, and `UNKNOWN`, plus `WRONG_PAGE_ASSIGNMENT`, `PAGE_NOT_PUBLISHED`, and the existing invalid-page refinements. Database and theme overrides are detected from the `WP_Block_Template` provenance that WordPress itself exposes; unsupported provenance remains `UNKNOWN` rather than being guessed. Directly comparable DB/theme override markup is normalized with WordPress `parse_blocks()` → `serialize_blocks()` before SHA-256 fingerprinting, so valid serialization whitespace does not create false drift while material block differences remain detectable. Registered plugin-template content is transformed by WordPress's native Block Hooks resolution path, so `CANONICAL` is established from proven plugin provenance and raw-source-versus-resolved content equivalence remains explicitly `NOT_PROVEN` rather than relying on private Core APIs.

The WU-04 Automated Qualification Lab slice creates deterministic synthetic fixtures for canonical state, wrong assignment, valid non-published page, missing/trashed/wrong-type targets, database override, theme override, missing canonical template, and an intentionally unclassifiable resolver result. Every fixture also snapshots relevant persistent state before/after diagnostics and verifies no hidden repair. A privacy-minimized text report is available through native read-only admin markup without JavaScript; it excludes page/form content, student data, uploads, authentication material, nonces, cookies and credentials.

WU-04 evidence is WordPress integration, not browser qualification. It does **not** prove Full Width geometry, browser RTL/accessibility/security behavior, Owner browser E2E/comprehension, the production host, or production qualification.

The approved Automated Qualification Lab extends the existing disposable runtime-lab for WU-04 through WU-07 where behavior can be reproduced honestly. It favors deterministic fixtures, real-browser assertions when needed, machine-readable evidence and useful failure artifacts. Human comprehension and irreducibly production-specific confirmation remain separate evidence requirements, and unavailable real dependencies must not be represented by synthetic PASS claims.

## Repository layout

```text
.
├── .github/
│   ├── workflows/
│   │   ├── wu01-runtime-facts.yml
│   │   ├── wu02-runtime-core.yml
│   │   ├── wu03-owner-settings.yml
│   │   └── wu04-diagnostics-drift.yml
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
│   └── runtime-lab/
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
