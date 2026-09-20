# SRWF Host Companion

Project-specific WordPress host integration layer for SRWF: deterministic block templates, full-width shells, runtime/template governance, diagnostics, and future bounded host-level integrations.

> **Current status:** repository foundation established; V0 architecture is frozen and approved. Functional template registration, settings UI, diagnostics, and production qualification have **not** been implemented yet.

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
4. [`docs/implementation/V0_IMPLEMENTATION_PLAN.md`](docs/implementation/V0_IMPLEMENTATION_PLAN.md) — bounded execution sequence.

The Mother Architecture is frozen for V0. Implementation may change low-level mechanisms only when the approved contracts and invariants remain intact.

## Platform policy

Current architectural baseline:

```text
Minimum WordPress: 6.7
Qualified WordPress: TO_BE_RECORDED from the exact SRWF runtime

Preferred engineering PHP floor: 8.2+
Minimum PHP: TO_BE_QUALIFIED from the real production host
Qualified PHP: TO_BE_RECORDED

Initial host theme: Twenty Twenty-Five
Qualified TT25 version: TO_BE_RECORDED
```

Do not convert these open values into guessed support claims.

## Repository status

The repository currently contains only foundation/scaffolding. The plugin bootstrap is intentionally inert.

```text
Architecture: APPROVED / FROZEN FOR V0
Repository foundation: ESTABLISHED
Template implementation: NOT_IMPLEMENTED
Admin settings UI: NOT_IMPLEMENTED
Runtime diagnostics: NOT_IMPLEMENTED
Browser/E2E qualification: NOT_RUN
Production qualification: NOT_PROVEN
Production release: NOT_PUBLISHED
```

## Repository layout

```text
.
├── .github/
│   └── PULL_REQUEST_TEMPLATE.md
├── docs/
│   ├── architecture/
│   │   ├── MOTHER_ARCHITECTURE.md
│   │   └── PPDM_ADOPTION.md
│   └── implementation/
│       └── V0_IMPLEMENTATION_PLAN.md
├── AGENTS.md
├── CHANGELOG.md
├── SECURITY.md
├── README.md
└── srwf-host-companion.php
```

Implementation directories such as `src/`, `templates/`, and `tests/` should be created when their first real contents are introduced. Do not add empty architecture for appearance.

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
