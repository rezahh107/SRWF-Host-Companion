# AGENTS.md — SRWF Host Companion

This file is the operating contract for coding agents and automated contributors working in `rezahh107/SRWF-Host-Companion`.

## 1. Repository identity

- **Product:** SRWF Host Companion
- **Type:** project-specific WordPress host-integration plugin
- **Primary consumer:** SRWF
- **Primary operator:** non-technical Owner / WordPress administrator
- **Canonical architecture:** `docs/architecture/MOTHER_ARCHITECTURE.md`
- **Owner-approved qualification amendment:** `docs/architecture/AUTOMATED_QUALIFICATION_LAB.md`
- **V0 status:** architecture approved and frozen; WU-01 prerequisite complete; WU-02 minimal runtime core, WU-03 Owner settings workflow, WU-04 diagnostics/drift, and WU-05 Full Width geometry are implemented on `main`; WU-06 admin browser RTL/security/accessibility qualification is implemented in PR #10 and automated-qualified on the pinned target tuple; WU-07 browser/E2E/comprehension/release-gate work remains open; Automated Qualification Lab includes WU-04 integration, WU-05 frontend-browser, and WU-06 admin-browser slices; production qualification `NOT_PROVEN`

The plugin exists to make SRWF host configuration deterministic, understandable, explicit, and verifiable.

It is not a theme, page builder, form system, workflow engine, or generic WordPress toolbox.

## 2. Mandatory preflight

Before any implementation, refactor, technical review, debugging, or architecture-sensitive planning:

1. read `docs/architecture/MOTHER_ARCHITECTURE.md`;
2. read this `AGENTS.md`;
3. read `docs/architecture/PPDM_ADOPTION.md` when the task affects admin UX, diagnostics, guidance, accessibility, i18n/RTL, or qualification;
4. read `docs/architecture/AUTOMATED_QUALIFICATION_LAB.md` when the task affects WU-04→WU-07 qualification strategy, CI evidence, browser testing, accessibility automation, release-candidate automation, or failure artifacts;
5. read `docs/implementation/V0_IMPLEMENTATION_PLAN.md` when the task belongs to V0;
6. inspect the current repository state and exact target runtime facts relevant to the task.

Do not claim a preflight occurred if the files were not actually inspected.

For version-sensitive WordPress behavior, current official target-version WordPress documentation/source and inspected runtime evidence outrank model memory or naming-pattern inference.

## 3. Authority model

For normative project decisions:

1. current explicit Owner instruction;
2. `docs/architecture/MOTHER_ARCHITECTURE.md` and explicit Owner-approved architecture amendments that do not contradict it;
3. accepted project-specific contracts/decisions;
4. this `AGENTS.md`;
5. applicable adopted guidance such as `PPDM_ADOPTION.md`.

For implementation facts:

1. exact supported/qualified runtime evidence;
2. current official WordPress documentation/source for the target version;
3. repository tests/evidence;
4. implementation inference.

Do not force one flat authority order across normative decisions and factual implementation behavior.

## 4. Frozen V0 mission

V0 solves one host-level problem:

> Let the Owner choose the SRWF Registration page and explicitly apply a canonical Full Width WordPress Block Template from a simple WordPress-native settings surface.

V0 includes:

- versioned role-based configuration with only `roles.registration.page_id`;
- one Registration Full Width template;
- explicit save/apply;
- read-only status check;
- page-validity diagnostics;
- template/drift diagnostics;
- Persian-first translatable admin UX;
- security and authorization guards;
- unit/integration/E2E evidence appropriate to the claim.

The approved Automated Qualification Lab strengthens how repeatable technical evidence is produced; it does not expand the product scope.

V0 does **not** include a separate Operational template unless architecture is amended after real Inbox / Entry Detail evidence.

## 5. Core boundaries

Preserve these owners:

```text
Twenty Twenty-Five → host/site shell
SRWF Host Companion → SRWF host integration
Gravity Forms → form behavior/data
GTB → Registration presentation
Gravity Flow → workflow behavior/state
GPP → operational presentation
PersianGravity → admitted Persian Gravity capabilities
Vazir/Vazirmatn → approved frontend typography
```

Do not move behavior across those boundaries merely because doing so seems convenient.

## 6. Forbidden architectural drift

Do not introduce without an explicit Owner-approved architecture amendment:

- Gravity Forms field styling or behavior;
- Gravity Flow workflow/permission/assignment behavior;
- GTB or GPP presentation ownership;
- a page builder;
- a custom workflow engine;
- custom database tables for business truth;
- a generic theme framework;
- a generic WordPress utility collection;
- a second admin design system;
- hidden automatic template repair;
- automatic mutation when rendering or checking status;
- speculative multi-page Registration cardinality;
- a separate Operational template in V0;
- frontend JavaScript without a proven need;
- bundled font delivery.

## 7. Native-first implementation policy

Use the smallest stable mechanism in this order:

```text
WordPress native capability/API
→ supported native admin pattern/component
→ minimal project-specific adapter
→ minimal scoped CSS if runtime proves necessary
→ JavaScript only for a proven residual gap
```

For V0 settings, PHP/native admin + WordPress Settings/Options APIs are the default.

React, SPA, custom settings frameworks, service-provider frameworks, capability registries, and adapter hierarchies require evidence of real complexity before adoption.

## 8. Configuration invariant

V0 configuration is versioned and role-based.

Conceptual contract:

```php
[
    'schema_version' => 1,
    'roles' => [
        'registration' => [
            'page_id' => 123,
        ],
    ],
]
```

Do not replace this with multiple competing options or assume `registration` is a list of pages.

Any future schema change requires version increment and defined migration behavior.

## 9. Mutation and security invariant

Keep these operations distinct:

```text
Open page
≠ Save selection
≠ Apply template
≠ Check status
≠ Repair drift
```

Rendering the settings page and `Check Again` are read-only.

A state-changing action must verify, at minimum:

```text
manage_options
+ valid nonce
+ authorization to edit the selected target page
+ validated page identity/type/status
```

Treat submitted IDs as untrusted input. Validate/sanitize at boundaries and escape output for its rendering context.

Nonce does not replace capability checks.

## 10. Do not guess WordPress persistence

The Mother Architecture intentionally does not freeze the internal stored representation of page-template assignment.

Do not assume the persistence value, metadata shape, source/origin field, or resolution behavior from naming conventions.

Prove the exact behavior on the qualified WordPress target through integration/runtime evidence before relying on it.

## 11. Layout hypothesis is not architecture

`H1` in the Mother Architecture is only the first prototype hypothesis:

```text
constrained outer shell + safe gutter
post-content per-block width override
```

If runtime evidence shows a cleaner native solution, implementation may change while preserving:

```text
Full Width application shell
+ safe mobile gutter
+ no global layout mutation
```

Do not turn H1 into an invariant.

## 12. Owner-facing UX rules

The primary Owner is non-technical.

Normal operation must not require knowledge of:

- PHP;
- template slugs;
- `wp_template`;
- post meta;
- `theme.json`;
- Site Editor internals;
- hashes or diagnostic codes.

The UI should explain:

```text
what this does
→ where to start
→ what the action changes
→ what happened
→ what remains uncertain
→ what to do next
```

Technical depth should be available through progressive disclosure, not required for the main path.

Use WordPress-native visual grammar. Avoid card explosion, jargon-first navigation, decorative complexity, or “SaaS inside wp-admin”.

## 13. State and diagnostics discipline

Use truthful state.

`UNKNOWN` is not `FAILED`.

Missing evidence is not success.

Diagnostics should separate:

```text
Evidence
Interpretation
Recommended next action
```

When a problem is user-facing, explain the practical consequence and next valid action. Do not dump raw internals as primary UX.

A copyable diagnostic report must exclude student data, Gravity entry values, uploads, workflow assignments, cookies, nonces, credentials, tokens, and unnecessary PII.

## 14. RTL, i18n and accessibility

- Owner-facing qualified UX is Persian-first.
- All user-facing strings must remain translatable with text domain `srwf-host-companion`.
- Technical identifiers remain readable/copyable LTR inside RTL.
- Prefer CSS logical properties.
- Use semantic HTML and native controls.
- Preserve keyboard usability and visible focus.
- Do not convey state by color alone.
- Accessibility baseline is WCAG 2.2 AA, but conformance claims require appropriate runtime and human evidence; automated accessibility scanning alone is not a conformance claim.

For the plugin's own wp-admin UI, native WordPress admin typography is the default unless a later approved requirement says otherwise.

## 15. Data and privacy

Never commit real student/customer/payment/PII data into:

- fixtures;
- screenshots;
- logs;
- diagnostic bundles;
- documentation;
- tests.

Use synthetic data only.

Do not add telemetry or external reporting in V0.

## 16. Dependency policy

Prefer no runtime dependency when WordPress Core provides the required primitive.

Do not add Composer/npm/runtime dependencies without documenting:

- the concrete gap;
- why native WordPress is insufficient;
- runtime/bundle impact;
- maintenance/security cost;
- compatibility consequence.

Do not freeze a minimum PHP version from preference alone. The real production PHP target must be inspected before support policy is locked.

For CI qualification, do not substitute a fake PASS when a real licensed/proprietary dependency required for the claim is unavailable. Preserve the affected claim as `NOT_PROVEN` or `ENVIRONMENT_UNAVAILABLE`.

## 17. Validation and claim ceiling

Use explicit evidence states:

```text
PASS
FAIL
NOT_RUN
NOT_PROVEN
ENVIRONMENT_UNAVAILABLE
```

Examples:

```text
unit PASS ≠ browser PASS
registration template registered ≠ page assignment proven
fixture state PASS ≠ Owner reachability proven
source inspection ≠ production runtime proof
desktop PASS ≠ 390px RTL PASS
automated accessibility scan PASS ≠ complete WCAG 2.2 AA conformance
disposable exact-version CI PASS ≠ production-host confirmation
browser workflow PASS ≠ human comprehension proven
```

Do not inflate a narrow test into a broader production-readiness claim.

For WU-04 through WU-07, prefer extending/reusing the existing disposable runtime-lab into deterministic qualification automation when the behavior can be reproduced faithfully. Preserve clear per-WU evidence boundaries; use real browser automation where browser evidence is required; retain diagnostic failure artifacts only when useful. Full/expensive qualification need not run on every trivial PR when focused checks plus a justified release-candidate/full-qualification boundary provide equal confidence.

## 18. Release gate

The authoritative Production Qualification / Release Gate is in the Mother Architecture.

A development build may exist before every gate passes.

Automated Qualification Lab success may support an `AUTOMATED_QUALIFICATION_PASS`-style bounded claim when defined by implementation evidence, but it does not replace unresolved human-comprehension or production-specific gates.

Do not call a build `PRODUCTION_QUALIFIED_FOR_SRWF` while a required gate is unresolved.

Do not publish a production release while the repository license is undeclared.

## 19. Repository workflow

The initial bootstrap/foundation may land directly on `main`.

After bootstrap, material changes should use focused branches and pull requests.

Recommended prefixes:

- `feat/`
- `fix/`
- `docs/`
- `test/`
- `chore/`

Each material PR should state:

- governing architecture/work unit;
- exact scope;
- behavior intentionally unchanged;
- files changed;
- tests/validation actually executed;
- unexecuted or runtime-sensitive checks;
- release impact.

Do not merge a material PR unless the Owner explicitly authorizes it or an approved automation policy later permits it.

## 20. Documentation discipline

Architecture decisions belong under `docs/architecture/`.

Execution sequencing belongs under `docs/implementation/`.

Do not silently rewrite the Mother Architecture while implementing code.

If implementation evidence invalidates an architectural assumption:

1. stop at the smallest affected boundary;
2. report the conflict;
3. keep the implementation claim `NOT_PROVEN` or incomplete;
4. amend architecture only with explicit Owner approval.

## 21. Bootstrap discipline

Do not create empty directories or placeholder abstractions merely to make the repository look complete.

Create `src/`, `templates/`, `tests/`, `assets/`, or release tooling when their first real contents are introduced.

The bootstrap plugin file may remain inert until the first implementation work unit.

## 22. Stop conditions

Stop and report instead of guessing when:

- the Mother Architecture or an applicable approved amendment is missing or contradictory;
- the exact WordPress behavior required by the implementation is unverified;
- the current production PHP version is needed but unknown;
- a requested change crosses an ownership boundary;
- a change requires silent/destructive repair;
- a test cannot exercise the target being claimed;
- a required dependency is unavailable and the requested claim would require pretending it was exercised;
- a release would exceed the evidence ceiling;
- a production release is requested before a license is explicitly selected.

Prefer explicit `NOT_PROVEN` to false closure.