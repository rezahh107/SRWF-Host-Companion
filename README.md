# SRWF Host Companion

Project-specific WordPress host integration layer for SRWF: deterministic block templates, full-width shells, runtime/template governance, diagnostics, and future bounded host-level integrations.

> **Current status:** V0 architecture is frozen and approved. WU-01 target-runtime facts and WU-02 minimal runtime core are complete. WU-03 Owner settings workflow is implemented in PR #6 with exact-target WordPress integration evidence; diagnostics/drift, Full Width geometry qualification, browser/E2E, accessibility/comprehension qualification, and production qualification are still pending.

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
2. [`AGENTS.md`](AGENTS.md) — operating contract for coding agents and automated contributors.
3. [`docs/architecture/PPDM_ADOPTION.md`](docs/architecture/PPDM_ADOPTION.md) — selectively adopted WordPress/self-guided UX guidance.
4. [`docs/implementation/V0_IMPLEMENTATION_PLAN.md`](docs/implementation/V0_IMPLEMENTATION_PLAN.md) — bounded execution sequence and current work-unit status.
5. [`docs/implementation/WU01_RUNTIME_FACTS.md`](docs/implementation/WU01_RUNTIME_FACTS.md) — target-runtime evidence and page-template contract.

The Mother Architecture is frozen for V0. Implementation may change low-level mechanisms only when the approved contracts and invariants remain intact.

## Platform policy

Current evidence baseline:

```text
Minimum WordPress: 6.7
Observed production WordPress: 7.1.1
WU-01/WU-02/WU-03 pinned qualified lab WordPress: 7.1.1

Preferred engineering PHP floor: 8.2+
Observed production PHP: 8.3.33
WU-01/WU-02/WU-03 pinned qualified lab PHP: 8.3.33
Minimum production PHP support policy: NOT_YET_FROZEN

Initial host theme: Twenty Twenty-Five
Observed production TT25: 1.5
WU-01/WU-02/WU-03 pinned qualified lab TT25: 1.5
```

Observed production identity provenance and disposable runtime behavior are distinct evidence classes. They do not by themselves establish production qualification.

## Repository status

```text
Architecture: APPROVED / FROZEN FOR V0
WU-01 runtime-fact prerequisite: COMPLETE_FOR_WU02
WU-02 minimal runtime core: IMPLEMENTED_ON_MAIN
Configuration schema v1: IMPLEMENTED
Registration template registration: IMPLEMENTED
Exact page-template assignment adapter: IMPLEMENTED
WU-03 Owner settings workflow: IMPLEMENTED / WORDPRESS_INTEGRATION_PROVEN_IN_PR_6
WU-04 runtime diagnostics/drift: NOT_IMPLEMENTED
WU-05 Full Width geometry qualification: NOT_PROVEN
WU-06 admin UX/security/RTL/accessibility qualification: NOT_RUN
WU-07 browser/E2E release gate: NOT_RUN
Production qualification: NOT_PROVEN
Production release: NOT_PUBLISHED
```

WU-03 adds the real Owner-facing workflow under `Settings → SRWF Host`: Persian-first first-run guidance, one WordPress-page selector backed only by schema-v1 `roles.registration.page_id`, the explicit `ذخیره و اعمال قالب تمام‌عرض` action, page-state classification, capability/nonce guards, canonical assignment/readback, page-change safety, and truthful bounded result messages. It introduces no admin or frontend JavaScript/CSS.

Exact-target WU-03 CI on PR #6 uses WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5`. The evidence level is WordPress integration. It does **not** prove browser/E2E behavior, Persian RTL visual quality, accessibility/comprehension, WU-04 diagnostics/drift, WU-05 Full Width geometry, or production qualification.

WU-02 remains the runtime owner of the canonical template registration and exact page-template assignment/readback primitive. WU-03 composes those existing primitives through an explicit authorized Owner action; rendering the settings screen does not assign or repair templates.

## Repository layout

```text
.
├── .github/
│   ├── workflows/
│   │   ├── wu01-runtime-facts.yml
│   │   ├── wu02-runtime-core.yml
│   │   └── wu03-owner-settings.yml
│   └── PULL_REQUEST_TEMPLATE.md
├── docs/
│   ├── architecture/
│   │   ├── MOTHER_ARCHITECTURE.md
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

A unit test does not prove browser behavior. Source inspection does not prove production runtime. A fixture that creates prerequisite state does not prove the Owner can reach that state through the real product path.

## License

A repository license has **not yet been selected**.

Do not publish or describe a production distribution as licensed for general use until the Owner explicitly chooses and records a license.
