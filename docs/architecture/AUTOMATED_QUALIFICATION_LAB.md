# SRWF Host Companion — Automated Qualification Lab Architecture Amendment

**Repository:** `rezahh107/SRWF-Host-Companion`  
**Document:** `docs/architecture/AUTOMATED_QUALIFICATION_LAB.md`  
**Date:** `2026-09-21`  
**Authority:** Owner-approved architecture amendment  
**Extends:** `docs/architecture/MOTHER_ARCHITECTURE.md` v0.5  
**Status:** `APPROVED`  
**Runtime dependency:** None  
**Purpose:** move repeatable observable qualification from manual checking into deterministic CI evidence without inflating CI into a production-equivalence claim.

---

## 1. Decision

SRWF Host Companion will extend the existing disposable runtime-lab into an **Automated Qualification Lab**.

This is not a second product, not a production simulator, and not a replacement for the existing WU sequence. It is the preferred evidence strategy for repeatable technical qualification across WU-04 through WU-07 where the relevant behavior can be reproduced honestly in CI.

The intended mental model is:

```text
GitHub Actions
    ↓
Exact disposable WordPress runtime
PHP + database + exact host theme
SRWF Host Companion from the exact tested commit/PR head
Required testable dependencies when lawfully and reliably available
    ↓
Controlled synthetic fixtures
    ↓
Real browser where browser evidence is required
    ↓
Admin workflow + frontend behavior
    ↓
Assertions + machine-readable evidence + diagnostic artifacts
```

The lab is an extension of the existing WU-01/WU-02/WU-03 runtime evidence approach. Do not build a parallel test platform when the existing provisioning can be reused cleanly.

---

## 2. Goal

Move the **largest practical share of repeatable technical qualification** into CI so that observable behavior can be re-proven on demand and regressions can be detected without repeating the same manual site checks for every change.

Do not encode an unsupported percentage target such as “80–90% automated.” Automation coverage is determined by what can be reproduced with trustworthy evidence, not by a quota.

The lab should optimize for:

- exact-head / exact-commit evidence;
- exact target runtime identity where required;
- deterministic fixtures;
- strong PASS/FAIL assertions;
- useful failure artifacts;
- isolation between evidence scopes;
- clear `NOT_RUN`, `NOT_PROVEN`, and `ENVIRONMENT_UNAVAILABLE` states when a claim cannot be exercised honestly.

---

## 3. Claim boundary

Automated qualification and production qualification are different claims.

The lab MAY support a bounded state such as:

```text
AUTOMATED_QUALIFICATION_PASS
```

when all required automated scopes for a given baseline have passed.

It MUST NOT, by itself, authorize:

```text
PRODUCTION_QUALIFIED_FOR_SRWF
```

The existing Mother Architecture Release Gate remains authoritative.

CI evidence is limited to what the disposable environment actually exercised. In particular:

```text
disposable exact-version runtime PASS
≠ production host confirmation

automated accessibility checks PASS
≠ complete WCAG 2.2 AA conformance

browser workflow PASS
≠ human comprehension proven

fixture dependency PASS
≠ production dependency integration proven when the real dependency was absent
```

---

## 4. Shared lab direction

Prefer shared, reusable provisioning for facts common across qualification work, for example:

```text
exact WordPress install
exact PHP runtime
exact qualified host theme
product plugin from exact tested commit
controlled synthetic content/roles/users
browser/runtime startup
common artifact collection
```

Each WU should retain its own explicit outcome and evidence boundary. Do not combine everything into one opaque green workflow if that makes failures harder to attribute.

The repository MAY use separate workflows, reusable workflows, composite actions, scripts, or another simple GitHub-native arrangement. Exact filenames and workflow topology are implementation details, not architecture locks.

Do not force the complete browser/qualification suite to run on every trivial change when path-scoped or release-candidate execution can preserve equal evidence quality at lower cost.

---

## 5. WU-04 — Diagnostics / drift automation target

WU-04 is a strong CI candidate.

Use controlled fixtures to exercise supported states such as:

```text
PAGE_VALID
PAGE_NOT_PUBLISHED
PAGE_MISSING
PAGE_TRASHED
PAGE_TYPE_INVALID

CANONICAL
WRONG_PAGE_ASSIGNMENT
CUSTOMIZED_DB_OVERRIDE
THEME_OVERRIDE
MISSING_TEMPLATE
UNKNOWN
```

For each supported state, qualification should verify where applicable:

- observed evidence is correct;
- interpretation/status is truthful;
- Owner-facing next-action guidance matches the real state;
- `Check Again` remains read-only;
- opening/rendering diagnostics does not repair or mutate state;
- customized or overridden state is not silently destroyed.

A fixture may deliberately create database/theme/template state when that is the correct way to reproduce the runtime condition under test.

---

## 6. WU-05 — Browser geometry automation target

WU-05 should use real browser/runtime evidence rather than source-only CSS inspection.

Required viewport matrix remains:

```text
320px
390px
430px
1440px
RTL
```

Browser assertions should measure the rendered page where practical, including:

- safe inline gutters;
- absence of unintended horizontal overflow;
- Registration application region escaping the unwanted narrow TT25 article constraint;
- shell/content remaining inside the usable viewport;
- header/footer/navigation presence and basic integrity;
- canonical template assignment/rendering.

Use actual layout measurements such as rendered element bounds when they provide stronger evidence than screenshots alone.

Screenshots are supporting evidence, not the sole PASS criterion.

Gravity Forms / GTB / related regression claims require the corresponding real dependency to be present in the exercised environment. If it is unavailable or cannot be installed lawfully/reliably, preserve the affected claim as `NOT_PROVEN` or `ENVIRONMENT_UNAVAILABLE` rather than substituting a fake PASS.

---

## 7. WU-06 — RTL / accessibility / security automation target

Automate the repeatable portion of WU-06, including where applicable:

### RTL / responsive admin

- Persian RTL environment and direction;
- usable narrow admin layout;
- technical identifiers remaining readable as LTR where applicable;
- no destructive horizontal overflow.

### Keyboard

Use real browser keyboard interaction for material controls where practical:

```text
Tab
Shift+Tab
Enter
Space
```

Verify usable focus movement and action reachability. Automated focus checks do not replace human judgment about the quality of the entire interaction order.

### Accessibility automation

Use an appropriate automated accessibility engine for repeatable detectable failures such as missing labels, invalid ARIA, duplicate IDs, and other machine-detectable issues.

Automated accessibility PASS MUST NOT be described as complete WCAG 2.2 AA conformance.

### Security / authorization

Prefer a combination of integration and real HTTP/browser tests where that provides stronger evidence. Exercise material cases such as:

- unauthorized user cannot access protected UI;
- required capability without target-page authorization is insufficient;
- missing/invalid nonce blocks mutation;
- crafted/invalid Page ID is rejected;
- wrong post type is rejected;
- read-only page render/check does not mutate configuration/template state.

---

## 8. WU-07 — Owner browser E2E automation target

Automate the mechanical Owner journey through the real browser where practical:

```text
login
→ reach Settings → SRWF Host (and/or Plugins → Settings link)
→ observe first-run guidance
→ select Registration page
→ execute explicit save/apply
→ observe truthful result
→ verify canonical configuration/assignment
→ open frontend Registration page
→ verify required runtime/browser conditions
→ return to settings / perform read-only verification when implemented
```

Relevant failure paths should also be exercised when they belong to the qualified workflow.

Browser automation can prove reachability, state transitions, visible UI presence, and mechanical operability. It cannot prove that a non-technical human actually understood the text or consequence of an action.

---

## 9. Human and production gates that remain outside substitution by CI

At minimum, these remain distinct evidence requirements when applicable:

### Human comprehension

The actual Owner/non-technical-user comprehension requirement remains human evidence.

Automation may verify that guidance, headings, labels, consequence text and actions exist, but it must not claim that a human understood them.

### Production-specific confirmation

CI cannot automatically substitute for facts that depend on the real production host or dependencies not faithfully reproduced in the lab.

Examples include:

- production-host-specific behavior not represented by the disposable runtime;
- production configuration/plugins unavailable in CI;
- licensed/proprietary dependencies that cannot be installed lawfully or reliably;
- external infrastructure behavior that fixtures do not faithfully reproduce.

Such items remain `NOT_PROVEN`, `NOT_RUN`, or `ENVIRONMENT_UNAVAILABLE` until the required evidence exists.

---

## 10. Failure artifacts / diagnosability

For browser or runtime qualification failures, retain only artifacts that materially improve diagnosis. Examples may include:

```text
screenshot-before.png
screenshot-after.png
page.html
console.log
server.log
browser-trace.zip
runtime-evidence.json
geometry.json
accessibility.json
```

Do not create artifact volume merely for completeness.

Artifacts and fixtures MUST remain privacy-safe and use synthetic data. Existing privacy boundaries remain unchanged.

---

## 11. Machine-readable qualification result

The lab should produce bounded machine-readable evidence when useful.

Conceptual example only:

```json
{
  "head_sha": "...",
  "environment": {
    "wordpress": "...",
    "php": "...",
    "theme": "..."
  },
  "wu04": "PASS",
  "wu05": "PASS",
  "wu06_automated": "PASS",
  "wu07_e2e": "PASS",
  "human_comprehension": "NOT_RUN",
  "production_host_confirmation": "NOT_RUN"
}
```

The exact schema is an implementation choice. Avoid building a general evidence platform or control plane.

---

## 12. Execution / cost policy

Use the lab where it improves evidence quality and repeatability.

Do not require every expensive browser/qualification scenario on every trivial PR by default.

A practical execution model may include:

- focused WU/path-relevant checks on ordinary PRs;
- broader cross-WU regression checks when a change can affect multiple proven outcomes;
- full automated qualification on release candidates, explicit manual dispatch, or another evidence-backed boundary.

The exact scheduling policy should be chosen from actual CI duration, failure cost, dependency availability and regression risk.

---

## 13. Non-goals

This amendment does NOT authorize:

- a production clone claim;
- synthetic PASS for unavailable dependencies;
- replacing Owner comprehension with text-presence assertions;
- declaring WCAG conformance from automated scanning alone;
- automatic production mutation/deployment;
- telemetry;
- real student/business data in CI;
- a generic CI platform unrelated to SRWF Host Companion;
- expanding V0 product scope beyond the frozen Registration host-integration mission.

---

## 14. Relationship to existing architecture

All existing Mother Architecture invariants, ownership boundaries, mutation rules, privacy requirements, exact-target claim ceiling, and Production Qualification / Release Gate remain in force.

This amendment changes the **preferred evidence strategy**, not the product ownership model or V0 functional scope.

If a future implementation cannot reproduce a required state faithfully, it must preserve the claim as unproven rather than weakening this boundary.
