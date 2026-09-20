# SRWF Host Companion — PPDM Adoption Note

**Source Repository:** `rezahh107/Personal-Preference-Decision-Model`  
**Inspected Main SHA:** `94ed79c9f8fe57357d2834ded5633d4767e4abe7`  
**Adoption Scope:** SRWF Host Companion architecture / admin UX / qualification only  
**Runtime Dependency:** None  
**Status:** Adopted guidance baseline

---

## 1. Why this source is relevant

SRWF Host Companion has a small technical core but a material comprehension requirement:

- the Owner is not expected to understand WordPress template internals;
- the plugin performs host-level configuration;
- it needs diagnostics and drift reporting;
- misleading status or hidden mutation would create operational confusion;
- the project requires Persian RTL and real runtime qualification.

Therefore two PPDM domains are materially relevant:

1. `wordpress-plugin-ui-ux`
2. `self-guided-product-ux`

Software qualification guidance is also relevant.

---

## 2. Adopted PPDM records

### WordPress Plugin UI/UX

- `knowledge/current/domains/wordpress-plugin-ui-ux/INDEX.md`
- `principles-and-native-design.md`
- `architecture-and-navigation.md`
- `interaction-forms-and-states.md`
- `diagnostics-security-performance.md`
- `accessibility-i18n-rtl-responsive.md`
- `testing-and-definition-of-done.md`
- `knowledge/current/defaults/DEF-WPUI-01.md`
- `knowledge/current/defaults/DEF-WP-01.md`

### Self-Guided Product UX

- `knowledge/current/domains/self-guided-product-ux/INDEX.md`
- `orientation-and-guidance.md`
- `states-and-result-semantics.md`
- `actions-and-runtime-contract.md`
- `verification-and-definition-of-done.md`

### Qualification

- `knowledge/current/defaults/DEF-QUAL-01.md`

---

## 3. Material rules transferred into SRWF Host Companion

### A. Native before custom

Adopted:

```text
WordPress native API/pattern
→ supported WordPress component/primitives
→ limited custom implementation
```

Practical effect:

- Settings/Options API preferred for simple settings;
- PHP/native admin is the V0 default;
- React/SPA/custom framework is not justified without real complexity.

### B. One admin surface, one responsibility

Adopted.

Practical effect:

```text
Settings → SRWF Host
```

V0 does not create a top-level menu or multiple pages.

### C. Self-guided Owner workflow

Adopted strongly.

The primary operator should understand from the UI itself:

```text
purpose
→ where to start
→ action consequence
→ result meaning
→ uncertainty
→ next step
```

Technical details remain optional.

### D. Explicit actions / no hidden mutation

Adopted strongly.

```text
Render ≠ Save ≠ Apply ≠ Check ≠ Repair
```

Opening the screen and `Check Again` are read-only.

Template assignment is an explicit Owner action.

### E. Truthful states

Adopted.

Unknown or insufficient evidence must remain unknown rather than being converted to synthetic success/failure.

Owner-facing wording is simple; internal evidence may be more precise.

### F. Contextual diagnostics

Adopted.

A problem should explain:

```text
current state
problem
practical consequence
next action
verification action
```

Raw technical data is secondary.

### G. Evidence / Interpretation / Recommendation separation

Adopted.

Diagnostics must not turn a detected symptom into an unsupported root-cause claim.

### H. Privacy-safe copyable diagnostics

Adopted as useful and low-risk.

A compact support/LLM-ready report may be provided, but must exclude student data, Gravity entry values, uploads, credentials, cookies, nonces and unnecessary PII.

### I. WordPress-native admin visual grammar

Adopted.

No parallel admin design system.

No card explosion.

Native admin typography by default.

### J. RTL/i18n/accessibility

Adopted.

- translatable UI strings;
- Persian RTL as first-class layout;
- LTR isolation for technical values;
- WCAG 2.2 AA baseline;
- keyboard/focus;
- responsive/narrow admin behavior.

### K. Asset scoping

Adopted.

Admin assets load only on plugin screens.

Frontend remains zero/minimal.

### L. Exact-target qualification

Adopted strongly.

```text
source review PASS
≠ runtime PASS

unit PASS
≠ browser PASS

fixture PASS
≠ Owner reachability PASS
```

Qualification claims are capped by actual evidence.

### M. Human comprehension verification

Adopted proportionally.

The core setup flow should receive:

- automated/runtime evidence;
- independent comprehension review where practical;
- Owner walkthrough because the Owner is the target non-technical operator and this workflow is the product's core value.

---

## 4. PPDM ideas intentionally NOT copied as automatic requirements

The following are not adopted by default:

- wizard;
- onboarding tour;
- Help Center;
- tooltip-heavy UI;
- React;
- SPA;
- large operational log;
- provider/integration topology;
- top-level admin menu;
- multiple admin pages;
- new scoring/classification system.

They may only enter if future evidence proves the simpler model insufficient.

---

## 5. Authority boundary

PPDM is not runtime authority.

It does not create WordPress behavior merely because a Markdown rule exists.

For SRWF Host Companion:

```text
Current Owner instruction
→ SRWF project architecture
→ official target-version WordPress behavior/docs
→ this plugin's accepted architecture/contracts
→ applicable PPDM guidance
→ historical examples
```

Version-sensitive implementation facts must be verified against the exact target WordPress/runtime.

---

## 6. Refresh rule

This adoption note is based on:

```text
PPDM main @ 94ed79c9f8fe57357d2834ded5633d4767e4abe7
```

Do not automatically import future PPDM changes into SRWF Host Companion.

A later PPDM change should affect this project only when:

1. it is materially relevant;
2. it does not conflict with current SRWF authority;
3. it is reviewed and explicitly admitted into this project's architecture or implementation.
