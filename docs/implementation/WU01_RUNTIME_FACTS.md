# WU-01 — Target Runtime Fact Capture

Status: `IN_PROGRESS / TARGET_IDENTITY_CAPTURED / EXACT_TARGET_LAB_PENDING`

Governing authority:

- `docs/architecture/MOTHER_ARCHITECTURE.md`
- `docs/implementation/V0_IMPLEMENTATION_PLAN.md` → WU-01
- `AGENTS.md` → exact-runtime evidence and no persistence guessing

## Purpose

WU-01 exists to replace assumptions about WordPress page-template behavior with observed facts before product feature code depends on them.

This document separates three different things that must not be collapsed:

1. **Current production target identity** — Owner-supplied WordPress Site Health evidence captured from the intended production host reports WordPress `7.1.1`, PHP `8.3.33`, environment type `production`, and active theme Twenty Twenty-Five (`twentytwentyfive`) `1.5`. This proves the current target identity, not SRWF Host Companion product qualification.
2. **Historical disposable CI lab evidence** — the accepted WU-01 run on PR #1 used WordPress `6.8.3`, PHP `8.2.33`, and Twenty Twenty-Five `1.3`. That run established the page-template assignment/persistence/fallback behavior on that older pinned tuple and remains valid historical evidence for that tuple.
3. **Exact-target disposable CI lab** — the WU-01 workflow is now repinned to WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5` so the same runtime facts can be falsified or reproduced against the exact current production version tuple without mutating the production host.

The production host previously had GeneratePress-related SRWF operational evidence, but the current Site Health evidence now reports TT25 `1.5` as the active production theme. Historical GeneratePress evidence must not be treated as the current host identity.

## Facts already documented from WordPress source

These are source-level facts, not substitutes for runtime proof:

- plugin block templates are registered through `register_block_template()` and can declare `post_types`;
- `get_block_templates()` merges eligible registry templates with theme/user templates and filters them for the requested post type;
- `WP_Theme::get_post_templates()` exposes eligible custom block templates through the page-template map using the block-template slug as the selectable key;
- page-template assignment is persisted through `_wp_page_template` by WordPress post/template APIs;
- block-template resolution can fall back from a theme-qualified template ID to the registered-template registry by slug;
- theme/user templates can outrank a matching plugin-registered template.

The architecture deliberately does not freeze the exact stored assignment value from source observations alone. Runtime evidence remains authoritative for that behavior.

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

A green exact-target WU-01 workflow would mean the **fact-capture harness executed successfully on a disposable runtime matching the current production WordPress/PHP/TT25 version tuple**. It would not mean:

- SRWF Host Companion product code has been run on production;
- browser/E2E qualification is complete;
- Full Width geometry is qualified;
- RTL/accessibility is qualified;
- production release gates have passed;
- WU-02 product implementation is production-qualified.

Direct production-host mutation is not required for this pre-implementation fact capture. Production qualification remains a later release-gate concern.

## Current gate to WU-02

The exact current target identity is now captured. The remaining WU-01 gate is to rerun the existing page-template registration/assignment/render/deactivation probe on the matching tuple `WordPress 7.1.1 / PHP 8.3.33 / Twenty Twenty-Five 1.5`.

If that exact-target run reproduces the required facts with `capture_status=COMPLETE`, WU-01 can close as an implementation prerequisite for WU-02 while production qualification remains explicitly `NOT_PROVEN`.
