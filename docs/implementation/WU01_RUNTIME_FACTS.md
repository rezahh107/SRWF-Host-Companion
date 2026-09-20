# WU-01 — Target Runtime Fact Capture

Status: `IN_PROGRESS / LAB_PROBE_ADDED / PRODUCTION_TARGET_NOT_PROVEN`

Governing authority:

- `docs/architecture/MOTHER_ARCHITECTURE.md`
- `docs/implementation/V0_IMPLEMENTATION_PLAN.md` → WU-01
- `AGENTS.md` → exact-runtime evidence and no persistence guessing

## Purpose

WU-01 exists to replace assumptions about WordPress page-template behavior with observed facts before product feature code depends on them.

This document separates three different things that must not be collapsed:

1. **Current SRWF operational evidence** — existing SRWF/GTB evidence currently proves a GeneratePress Full Width host configuration for the Registration surface. That is current project evidence, not proof that SRWF Host Companion has qualified Twenty Twenty-Five.
2. **Disposable CI lab target** — the WU-01 probe uses WordPress `6.8.3`, PHP `8.2.33`, and Twenty Twenty-Five `1.3`. WordPress/PHP reuse the already-evidenced SRWF CI runtime baseline; TT25 `1.3` is the version bundled with WordPress `6.8.3`.
3. **Production/staging target** — exact production WordPress, PHP, and TT25 versions remain `NOT_PROVEN` until captured from the intended target runtime. The lab tuple must not be promoted into a production support claim.

## Facts already documented from WordPress 6.8.3 source

These are source-level facts, not substitutes for runtime proof:

- plugin block templates are registered through `register_block_template()` and can declare `post_types`;
- `get_block_templates()` merges eligible registry templates with theme/user templates and filters them for the requested post type;
- `WP_Theme::get_post_templates()` exposes eligible custom block templates through the page-template map using the block-template slug as the selectable key;
- page-template assignment is persisted through `_wp_page_template` by WordPress post/template APIs;
- block-template resolution can fall back from a theme-qualified template ID to the registered-template registry by slug;
- theme/user templates can outrank a matching plugin-registered template.

The architecture deliberately does not freeze the exact stored assignment value from these source observations alone.

## Disposable lab probe

`.github/workflows/wu01-runtime-facts.yml` builds an isolated WordPress runtime and captures machine-readable evidence without enabling any SRWF Host Companion product feature.

The probe checks:

- exact WordPress/PHP/TT25 lab identity;
- plugin template registration for `page`;
- the page-template map key WordPress actually exposes;
- `wp_update_post( page_template )` assignment using that observed key;
- the raw `_wp_page_template` value after assignment;
- `get_page_template_slug()` read-back;
- block-template object resolution while the probe plugin is active;
- real frontend rendering of both a template marker and post-content marker;
- fresh-process persistence/resolution facts after the fixture plugin is deactivated;
- real frontend fallback after deactivation, including preservation of the synthetic page content.

The fixture plugin and synthetic page exist only inside the disposable CI runtime. They contain no real data or PII.

## Claim ceiling

A green WU-01 workflow means the **fact-capture harness executed successfully on the pinned disposable lab tuple**. It does not mean:

- the production host runs those versions;
- TT25 is already the active production theme;
- browser/E2E qualification is complete;
- the final SRWF Host Companion assignment adapter has been selected;
- WU-02 product implementation is production-qualified.

Production/staging facts remain `NOT_PROVEN` until the intended runtime is directly inspected.

## Current gate to WU-02

WU-02 must not guess the assignment adapter or freeze a minimum PHP requirement from preference. After the lab evidence is reviewed, the project still needs the exact intended target runtime identity before WU-01 can be truthfully closed under the frozen V0 architecture.
