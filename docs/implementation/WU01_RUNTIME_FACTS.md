# WU-01 — Target Runtime Fact Capture

Status: `COMPLETE_FOR_WU02 / PRODUCTION_QUALIFICATION_NOT_PROVEN`

Governing authority:

- `docs/architecture/MOTHER_ARCHITECTURE.md`
- `docs/implementation/V0_IMPLEMENTATION_PLAN.md` → WU-01
- `AGENTS.md` → exact-runtime evidence, explicit evidence states, and no persistence guessing

## Purpose

WU-01 exists to replace assumptions about WordPress page-template behavior with observed facts before product feature code depends on them.

This document separates three different evidence classes that must not be collapsed:

1. **Production target identity provenance** — the Owner supplied a WordPress Site Health → Info runtime export from the production environment. The minimum WU-01 identity subset is preserved in `docs/evidence/PRODUCTION_SITE_HEALTH_IDENTITY.md`, making the production-host identity claim reviewable inside the repository evidence surface.
2. **Historical disposable CI lab evidence** — the accepted WU-01 run on PR #1 used WordPress `6.8.3`, PHP `8.2.33`, and Twenty Twenty-Five `1.3`. That run established the page-template assignment/persistence/fallback behavior on that older pinned tuple and remains valid historical evidence for that tuple.
3. **Current disposable CI lab evidence** — the same WU-01 probe successfully reproduced the required runtime facts on the pinned disposable tuple WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5`. This is `RUNTIME_PROVEN` for that CI tuple.

The repository-preserved production Site Health identity and the current disposable CI tuple match on the WU-01 version dimensions that matter here: WordPress `7.1.1`, PHP `8.3.33`, and active Twenty Twenty-Five `1.5`.

This closes the pre-implementation target-identity prerequisite without claiming direct SRWF Host Companion execution on the production host.

Historical GeneratePress-related SRWF operational evidence must not be used to infer current production host identity; the current preserved Site Health evidence identifies Twenty Twenty-Five `1.5` as active for the reported production snapshot.

## Production target identity — `DOCUMENTED`

Reviewable basis:

- `docs/evidence/PRODUCTION_SITE_HEALTH_IDENTITY.md`

Preserved Owner-supplied Site Health facts:

- WordPress `7.1.1`;
- PHP `8.3.33` 64-bit;
- active theme Twenty Twenty-Five `1.5`;
- stylesheet/theme slug `twentytwentyfive`;
- WordPress environment type `production`;
- Site Health report current timestamp `2026-09-20T20:42:57+00:00`.

The source is an Owner-supplied runtime report preserved in-repository. It is not a cryptographically signed or independently collected host attestation. Unrelated Site Health details are intentionally excluded for privacy/minimization.

## Facts already documented from WordPress source

These are source-level facts, not substitutes for runtime proof:

- plugin block templates are registered through `register_block_template()` and can declare `post_types`;
- `get_block_templates()` merges eligible registry templates with theme/user templates and filters them for the requested post type;
- `WP_Theme::get_post_templates()` exposes eligible custom block templates through the page-template map using the block-template slug as the selectable key;
- page-template assignment is persisted through `_wp_page_template` by WordPress post/template APIs;
- block-template resolution can fall back from a theme-qualified template ID to the registered-template registry by slug;
- theme/user templates can outrank a matching plugin-registered template.

The architecture deliberately does not freeze the exact stored assignment value from source observations alone. Runtime evidence remains authoritative for that behavior.

## Current disposable-lab runtime facts — `RUNTIME_PROVEN`

The disposable WU-01 probe completed with `capture_status=COMPLETE` on:

- WordPress `7.1.1`;
- PHP `8.3.33`;
- Twenty Twenty-Five `1.5`;
- stylesheet `twentytwentyfive`.

Accepted final PR #2 exact-head evidence:

- PR Head: `cda79a0e5eea848e8a05f8bf50c48e61800682a3`;
- workflow run: `35537484523`;
- workflow conclusion: `success`;
- artifact id: `10612777971`;
- artifact digest: `sha256:2fe40dad58489e34d6fec092e1894a5331c113aa97575bc72ae698838fd810b0`;
- artifact capture status: `COMPLETE`.

Observed behavior in that disposable lab:

- the plugin template is registered for `page`;
- registration identity is `srwf-host-companion//registration-full-width`;
- the page-template map exposes the selectable key `registration-full-width`;
- `wp_update_post( page_template )` succeeds with `registration-full-width`;
- `_wp_page_template` persists `registration-full-width`;
- `get_page_template_slug()` reads back `registration-full-width`;
- while the probe plugin is active, WordPress resolves the template as `twentytwentyfive//registration-full-width` with plugin origin/source;
- the active frontend returns measured HTTP `200` and renders both the template marker and page-content marker;
- after deactivation, the persisted assignment remains `registration-full-width` while the plugin template no longer resolves;
- the fallback frontend still returns measured HTTP `200`, no longer renders the plugin template marker, and preserves the page-content marker.

These observations reproduce the same assignment/persistence/fallback contract previously seen on the older lab tuple and remove the version-coupling uncertainty relevant to WU-02's assignment adapter.

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

## Evidence classification

| Claim | State | Reviewable basis |
| --- | --- | --- |
| Current reported production host identity: WordPress `7.1.1` / PHP `8.3.33` / active TT25 `1.5` | `DOCUMENTED` | repository-preserved Owner-supplied Site Health identity evidence |
| WordPress `7.1.1` / PHP `8.3.33` / TT25 `1.5` disposable runtime behavior | `RUNTIME_PROVEN` | successful run `35537484523` and artifact `10612777971` |
| Registration / assignment / active render / deactivation persistence / fallback behavior on that disposable tuple | `RUNTIME_PROVEN` | machine-readable artifact from the successful final PR #2 run |
| Direct SRWF Host Companion behavior on the production host | `NOT_PROVEN` | product feature code has not been run there |
| Browser/E2E, Full Width, RTL/accessibility and release-gate qualification | `NOT_PROVEN` | later work units/release gates not completed |
| Production qualification | `NOT_PROVEN` | release gates not completed |

## Claim ceiling

Closing WU-01 means the project now has enough reviewable target identity and exact-version runtime behavior evidence to implement WU-02 without guessing the page-template assignment contract.

It does **not** mean:

- SRWF Host Companion product code has been run on production;
- browser/E2E qualification is complete;
- Full Width geometry is qualified;
- RTL/accessibility is qualified;
- production release gates have passed;
- WU-02 product implementation is production-qualified.

Direct production-host mutation is not required for this pre-implementation fact capture.

## WU-02 readiness

WU-01 is **complete as the runtime-fact prerequisite for WU-02**.

The implementation-relevant facts are now established without guessing:

- the production Site Health identity report is preserved and reviewable in-repository;
- its WordPress/PHP/TT25 tuple matches the pinned disposable lab tuple;
- the exact page-template selectable/persisted value is `registration-full-width` in the proven disposable runtime;
- active resolution uses `twentytwentyfive//registration-full-width` for the registered plugin template;
- deactivation preserves the assignment value and falls back without losing page content.

WU-02 may use these facts to implement the minimal runtime core and exact assignment adapter described by the frozen architecture.

`PRODUCTION_QUALIFIED_FOR_SRWF` remains `NOT_PROVEN` until the later release gates are completed.
