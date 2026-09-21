# SRWF Host Companion — Inbox Full Width Host Canvas Architecture Amendment

**Repository:** `rezahh107/SRWF-Host-Companion`  
**Date:** `2026-09-21`  
**Authority:** Owner-approved post-`v0.1.0` architecture amendment  
**Extends:** `docs/architecture/MOTHER_ARCHITECTURE.md` v0.5  
**Status:** `APPROVED / BOUNDED`  
**Runtime dependency:** None

---

## 1. Decision

A real product need now exists for one independently selected **Inbox Page** to use the same SRWF Full Width host canvas already proven for Registration.

This amendment admits exactly two SRWF page roles:

```text
registration
inbox
```

The Inbox need is **host canvas only**. It does not authorize an Operational presentation layer or a second product template.

---

## 2. Reused host canvas

Inbox reuses the existing canonical template identity and content:

```text
TemplateRegistrar::TEMPLATE_SLUG
registration-full-width
```

The existing `templates/registration-full-width.html` canvas remains canonical for both roles.

This amendment does **not** authorize:

- a separate Inbox template;
- an Operational template variant;
- renamed/reworked template markup merely for naming symmetry;
- different Inbox geometry, gutters, header/footer/navigation composition, or frontend shell behavior.

The historical Registration-oriented template name is retained because the already-proven implementation is the intended Inbox host canvas and changing identity is unnecessary risk.

---

## 3. Ownership boundary

SRWF Host Companion owns only the WordPress host-page concern for Inbox:

```text
select one WordPress Page
→ persist its page ID
→ explicitly assign the existing SRWF Full Width template
→ verify assignment readback during that explicit operation
```

It does **not** own or change:

- Gravity Flow Inbox behavior or content;
- workflow state;
- Gravity Flow assignments or permissions;
- Gravity Forms behavior;
- GPP presentation;
- Inbox internal styling;
- Page Builder behavior;
- generic full-width handling for arbitrary pages.

Gravity Flow and GPP remain authoritative for their existing responsibilities.

---

## 4. Configuration evolution

The single existing option remains authoritative:

```text
srwf_host_companion_config
```

Schema v2 adds one bounded role while preserving one page per role:

```php
[
    'schema_version' => 2,
    'roles' => [
        'registration' => [
            'page_id' => 123,
        ],
        'inbox' => [
            'page_id' => 456,
        ],
    ],
]
```

Migration rules:

- valid schema-v1 Registration state remains readable without persistent mutation;
- Inbox is unconfigured (`0`) when reading schema v1;
- opening/rendering settings does not migrate or write state;
- explicit Inbox persistence is the schema-v1 → schema-v2 persistence boundary;
- once schema v2 exists, Registration and Inbox setters preserve the other role;
- malformed or unsupported state remains fail-closed/unconfigured;
- no second option or competing source of truth is introduced.

Registration-only legacy persistence may remain schema v1 until Inbox is explicitly configured. This minimizes unnecessary state churn while keeping the in-memory role view compatible with the new capability.

---

## 5. Owner workflow

The existing single surface remains authoritative:

```text
Settings → SRWF Host
```

Registration and Inbox are independent explicit operations.

Applying Inbox must not re-save or re-assign Registration. Applying Registration must not re-save or re-assign Inbox.

Both operations retain the existing authorization and mutation model:

- `manage_options`;
- valid role-specific nonce;
- valid WordPress Page target;
- target `edit_post` capability;
- explicit template assignment through `PageTemplateAssignment`;
- assignment readback verification;
- no page-content mutation;
- no automatic rewrite/reversion of the previously selected page.

---

## 6. Diagnostics boundary

The existing diagnostics remain **Registration-focused**.

This amendment does not authorize Inbox-specific drift detection, repair, automatic reassignment, or a generalized role diagnostics subsystem.

Owner-facing wording may clarify this boundary so Registration diagnostics are not misread as Inbox coverage.

---

## 7. Qualification and claim ceiling

The existing Automated Qualification Lab is reused.

Synthetic WordPress Inbox content is sufficient to qualify the narrow host-canvas claim when the exact pinned lab tuple exercises:

- canonical Inbox page assignment/readback;
- the same Full Width host canvas at `320`, `390`, `430`, and `1440` CSS px;
- RTL;
- safe gutters;
- no horizontal document overflow;
- preserved Twenty Twenty-Five header/footer/navigation.

The following remain separate claims unless directly exercised:

```text
Real Gravity Flow Inbox integration: NOT_PROVEN
GPP integration: NOT_PROVEN
Human comprehension: NOT_PROVEN
Production-host confirmation: NOT_PROVEN
PRODUCTION_QUALIFIED_FOR_SRWF: NOT_PROVEN
```

Absence of real Gravity Flow is not a blocker for this host-canvas-only capability, but it may not be promoted into a Gravity Flow integration claim.

---

## 8. Relationship to the frozen Mother Architecture

The Mother Architecture remains frozen and is not silently rewritten.

This document is the narrow post-`v0.1.0` exception recording the Owner's now-confirmed Inbox need. All unchanged ownership, privacy, mutation, release, diagnostics, and evidence boundaries in the Mother Architecture and later approved qualification/release amendments remain in force.
