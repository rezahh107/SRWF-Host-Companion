# WU-01 — Target Runtime Fact Capture

Status: `TARGET_IDENTITY_NOT_PROVEN / PRODUCTION_QUALIFICATION_NOT_PROVEN`

Governing authority:

- `docs/architecture/MOTHER_ARCHITECTURE.md`
- `docs/implementation/V0_IMPLEMENTATION_PLAN.md` → WU-01
- `AGENTS.md` → exact-runtime evidence, explicit evidence states, and no persistence guessing

## Purpose

WU-01 exists to replace assumptions about WordPress page-template behavior with observed facts before product feature code depends on them.

This document separates three different things that must not be collapsed:

1. **Production target identity provenance** — the intended production host's exact WordPress, PHP, active-theme, and environment identity must be backed by independently reviewable production-host evidence preserved or referenced in the PR evidence surfaces. No such reviewable production Site Health artifact/reference is currently present, so production target identity is `NOT_PROVEN`.
2. **Historical disposable CI lab evidence** — the accepted WU-01 run on PR #1 used WordPress `6.8.3`, PHP `8.2.33`, and Twenty Twenty-Five `1.3`. That run established the page-template assignment/persistence/fallback behavior on that older pinned tuple and remains valid historical evidence for that tuple.
3. **Current disposable CI lab evidence** — the same WU-01 probe successfully reproduced the required runtime facts on the pinned disposable tuple WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5`. This is `RUNTIME_PROVEN` for that CI tuple only; without preserved production-host identity provenance, it does not prove that the tuple is the current production host tuple.

An Owner-supplied production Site Health assertion was previously described as establishing the current production target identity, but the corresponding production Site Health artifact/reference is not preserved in the reviewable PR evidence surfaces. The asserted values are therefore not treated as false; their production-host provenance remains `NOT_PROVEN` until independently reviewable evidence is supplied.

Historical GeneratePress-related SRWF operational evidence also must not be used to infer current production host identity.

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

Accepted exact-head evidence:

- PR Head: `f247164cc29d10b206b0abac01b6a09d89e46d64`;
- workflow run: `35536662522`;
- job: `106146804303`;
- workflow conclusion: `success`;
- artifact id: `10613172501`;
- artifact digest: `sha256:9511ca86e583a8107f0d4516e5d0818368d5149e6fc615152da818387a26845f`;
- artifact capture status: `COMPLETE`.

Observed behavior in that disposable lab:

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

These observations reproduce the same assignment/persistence/fallback contract previously seen on the older lab tuple. They remove version-coupling uncertainty **inside the pinned disposable tuple**, but they do not establish the identity of the production host.

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
| WordPress `7.1.1` / PHP `8.3.33` / TT25 `1.5` disposable runtime behavior | `RUNTIME_PROVEN` | successful run `35536662522` and artifact `10613172501` |
| Registration / assignment / active render / deactivation persistence / fallback behavior on that disposable tuple | `RUNTIME_PROVEN` | machine-readable artifact from the successful run |
| Those version values identify the current production host | `NOT_PROVEN` | no preserved/reviewable production Site Health artifact/reference is currently present |
| Direct SRWF Host Companion behavior on the production host | `NOT_PROVEN` | disposable lab only |
| Production qualification | `NOT_PROVEN` | later release gates not completed |

## Claim ceiling

A green WU-01 workflow means the **fact-capture harness executed successfully on its pinned disposable runtime**. It does not mean:

- the disposable tuple has been proven to be the current production host tuple;
- SRWF Host Companion product code has been run on production;
- browser/E2E qualification is complete;
- Full Width geometry is qualified;
- RTL/accessibility is qualified;
- production release gates have passed;
- WU-02 product implementation is production-qualified.

Direct production-host mutation is not required for this pre-implementation fact capture. However, WU-01's production-target identity prerequisite cannot be closed from a disposable lab alone.

## WU-02 readiness

WU-01 is **not complete for WU-02** while production-target identity provenance is absent.

The runtime behavior probe itself is not the blocker: the pinned WordPress `7.1.1` / PHP `8.3.33` / Twenty Twenty-Five `1.5` disposable tuple is successfully `RUNTIME_PROVEN`. The only remaining WU-02 gate represented here is independently reviewable evidence that establishes the intended production host identity and allows the project to determine whether that production tuple is the same tuple already exercised by the lab.

Until that provenance is supplied and reviewed:

- production target identity remains `NOT_PROVEN`;
- WU-01 remains `TARGET_IDENTITY_NOT_PROVEN`;
- WU-02 must not be entered on the premise that the current production tuple has already been proven;
- the valid disposable-lab behavior evidence remains preserved and may be reused once target identity is established.

This evidence-state correction does not invalidate the successful runtime probe and does not change any runtime workflow behavior. `PRODUCTION_QUALIFIED_FOR_SRWF` also remains `NOT_PROVEN` until the later release gates are completed.
