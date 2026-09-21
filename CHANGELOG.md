# Changelog

All notable project changes should be recorded here.

The project has not published a production release.

## [Unreleased]

### Added

- approved V0 Mother Architecture;
- PPDM adoption record for relevant WordPress/self-guided UX guidance;
- Owner-approved Automated Qualification Lab architecture amendment for repeatable WU-04→WU-07 technical qualification in CI while preserving production/human claim boundaries;
- repository operating contract (`AGENTS.md`);
- bounded V0 implementation plan;
- WordPress plugin bootstrap and minimal WU-02 runtime loader;
- privacy-minimized production Site Health identity evidence for WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5`;
- WU-01 exact-runtime fact workflow and disposable page-template evidence harness;
- schema-v1 configuration storage under `srwf_host_companion_config` with `roles.registration.page_id`;
- canonical Registration Block Template registration for `srwf-host-companion//registration-full-width`;
- exact page-template assignment/readback adapter using the proven persisted/selectable slug `registration-full-width`;
- WU-02 exact-target integration workflow covering bootstrap, configuration, template registration, assignment, no-hidden-mutation, active host composition, and deactivation/fallback;
- WU-03 native `Settings → SRWF Host` Owner workflow with Persian-first first-run guidance, one Registration Page selector, explicit save/apply, page validity classification, authorization/nonce guards, page-change safety, and truthful bounded result handling;
- WU-03 exact-target WordPress integration workflow covering side-effect-free render, authorization failures, invalid/missing/trashed/wrong-type targets, non-published pages, schema-v1 persistence, canonical assignment/readback, and previous-page no-rewrite behavior;
- WU-04 read-only diagnostics on the existing settings screen in PR #8, including `بررسی دوباره`, page/assignment/provider evidence, practical Persian guidance, progressive technical details, source-level fingerprints, and a privacy-minimized read-only support report;
- first functional Automated Qualification Lab slice in PR #8: deterministic WU-04 fixtures for canonical, wrong-assignment, non-published, missing/trashed/wrong-type page, published/draft/trashed database template candidates, theme override, missing template, `UNKNOWN`, native Block Hooks falsification, material-drift positive controls, and per-fixture no-hidden-repair assertions;
- dedicated WU-04 exact-target workflow on WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5`;
- repository hygiene and pull-request foundation.

### Status

```text
Architecture: APPROVED / FROZEN FOR V0 + OWNER-APPROVED QUALIFICATION-LAB AMENDMENT
WU-01: COMPLETE_FOR_WU02
WU-02: IMPLEMENTED_ON_MAIN
WU-03 Owner settings workflow: IMPLEMENTED_ON_MAIN / WORDPRESS_INTEGRATION_PROVEN
WU-04 diagnostics/drift: IMPLEMENTED_IN_PR_8 / WORDPRESS_INTEGRATION_QUALIFIED_ON_PINNED_TUPLE
WU-05 Full Width geometry: NOT_PROVEN
WU-06 admin UX/security/RTL/accessibility qualification: NOT_RUN
WU-07 browser/E2E/release gate: NOT_RUN
Automated Qualification Lab: FIRST_FUNCTIONAL_SLICE_WU04_IMPLEMENTED_IN_PR_8
Production qualification: NOT_PROVEN
Production release: NOT_PUBLISHED
```

WU-04 in PR #8 reads current state only. Opening diagnostics or using `بررسی دوباره` does not save configuration, assign/repair templates, alter page content, rewrite customized `wp_template` content, or modify theme files.

Provider truth now follows the same exact-slug `get_block_templates()` candidate model used by WordPress frontend resolution. On WordPress `7.1.1`, normal non-`wp_id` database candidates are published-only, so draft and trashed `wp_template` objects are not promoted to active `CUSTOMIZED_DB_OVERRIDE` providers; a published matching database override remains eligible and wins before theme/plugin fallbacks.

Content drift is evaluated from raw, pre-Block-Hooks source bytes rather than from transformed `WP_Block_Template->content`: persisted `wp_template` `post_content` for a proven active DB provider, and the actual active child/parent theme template file located through WordPress theme/folder APIs for a proven theme provider. Raw markup is normalized with `serialize_blocks(parse_blocks(content))` before SHA-256 comparison. Dedicated native Block Hooks fixtures prove canonically equivalent DB/theme raw source does not become a false mismatch after Core transforms returned content, while materially changed raw DB/theme source still produces a mismatch. If raw source cannot be proven, comparison remains `NOT_PROVEN` with no manufactured true/false result.

Registered plugin provider identity remains proven from WordPress provenance; direct raw-source-versus-post-resolution plugin content equivalence remains `NOT_PROVEN_NATIVE_BLOCK_HOOKS_TRANSFORM`.

WU-04 evidence is integration-level Automated Qualification Lab evidence only. WU-05 browser geometry, WU-06 browser/admin RTL/accessibility/security qualification, WU-07 mechanical Owner browser E2E plus human comprehension, production-host confirmation, and Production Qualification remain outside this claim.
