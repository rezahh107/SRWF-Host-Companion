# WU-01 — Target Runtime Fact Capture

Status: `COMPLETE_FOR_WU02 / PRODUCTION_QUALIFICATION_NOT_PROVEN`

Governing authority:

- `docs/architecture/MOTHER_ARCHITECTURE.md`
- `docs/implementation/V0_IMPLEMENTATION_PLAN.md` → WU-01
- `AGENTS.md` → exact-runtime evidence and no persistence guessing

## Purpose

WU-01 exists to replace assumptions about WordPress page-template behavior with observed facts before product feature code depends on them.

This document separates three different things that must not be collapsed:

1. **Current production target identity** — Owner-supplied WordPress Site Health evidence captured from the intended production host reports WordPress `7.1.1`, PHP `8.3.33`, environment type `production`, and active theme Twenty Twenty-Five (`twentytwentyfive`) `1.5`. This proves the current target identity, not SRWF Host Companion product qualification.
2. **Historical disposable CI lab evidence** — the accepted WU-01 run on PR #1 used WordPress `6.8.3`, PHP `8.2.33`, and Twenty Twenty-Five `1.3`. That run established the page-template assignment/persistence/fallback behavior on that older pinned tuple and remains valid historical evidence for that tuple.
3. **Exact-target disposable CI lab evidence** — the same WU-01 probe has now reproduced the required runtime facts on WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5`, matching the current production version tuple while remaining isolated from the production host.

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

## Exact-target runtime facts

The disposable exact-target probe completed with `capture_status=COMPLETE` on:

- WordPress `7.1.1`;
- PHP `8.3.33`;
- Twenty Twenty-Five `1.5`;
- stylesheet `twentytwentyfive`.

Observed behavior:

- the plugin template is registered for `page`;
- registration identity is `srwf-host-companion//registration-full-width`;
- the page-template map exposes the selectable key `registration-full-width`;
- `wp_update_post( page_template )` succeeds with `registration-full-width`;
- `_wp_page_template` persists `registration-full-width`;
- `get_page_template_slug()` reads back `registration-full-width`;
- while the probe plugin is active, WordPress resolves the template as `twentytwentyfive//registration-full-width` with plugin origin/source;
- the active frontend returns HTTP `200` and renders both the template marker and page-content marker;
- after deactivation, the persisted assignment remains `registration-full-width` while the plugin template no longer resolves;
- the fallback frontend still returns HTTP `200`, no longer renders the plugin template marker, and preserves the page-content marker.

These observations reproduce the same assignment/persistence/fallback contract previously seen on the older lab tuple and remove the version-coupling uncertainty that blocked WU-02.

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

A green exact-target WU-01 workflow means the **fact-capture harness executed successfully on a disposable runtime matching the current production WordPress/PHP/TT25 version tuple**. It does not mean:

- SRWF Host Companion product code has been run on production;
- browser/E2E qualification is complete;
- Full Width geometry is qualified;
- RTL/accessibility is qualified;
- production release gates have passed;
- WU-02 product implementation is production-qualified.

Direct production-host mutation is not required for this pre-implementation fact capture. Production qualification remains a later release-gate concern.

## WU-02 readiness

WU-01 is closed as an implementation prerequisite. WU-02 may now implement the minimal runtime core using the observed contract rather than a guessed one:

- canonical Registration template registration remains namespaced at registration time;
- the page assignment adapter should persist/use the observed selectable slug `registration-full-width`;
- runtime resolution should be checked against the actual resolved block-template state rather than inferred from persistence alone;
- deactivation must be treated as a state where assignment metadata can remain while the plugin template is unavailable and WordPress falls back without losing page content.

This is implementation readiness only. `PRODUCTION_QUALIFIED_FOR_SRWF` remains `NOT_PROVEN` until the later release gates are completed.
