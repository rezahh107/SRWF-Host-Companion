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
- WU-04 read-only diagnostics on the existing settings screen in PR #8, including `بررسی دوباره`, page/assignment/template-provider evidence, practical Persian guidance, progressive technical details, normalized fingerprints, and a privacy-minimized read-only support report;
- first functional Automated Qualification Lab slice in PR #8: deterministic WU-04 fixtures for canonical, wrong-assignment, non-published, missing/trashed/wrong-type page, database override, theme override, missing template, and `UNKNOWN` fallback with per-fixture no-hidden-repair assertions and machine-readable evidence;
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

WU-04 in PR #8 reads current state only. Opening diagnostics or using `بررسی دوباره` does not save configuration, assign/repair templates, alter page content, rewrite customized `wp_template` content, or modify theme files. On the pinned disposable WordPress 7.1.1 tuple, DB, theme-file, registered-plugin and missing/unknown resolution behavior is exercised through deterministic fixtures rather than inferred from naming conventions.

For directly comparable DB/theme override markup, the WU-04 normalization contract uses `serialize_blocks(parse_blocks(content))` before SHA-256 comparison; the qualification fixture proves valid serialization whitespace normalizes equivalently while an added material block remains different. WordPress transforms registered plugin-template content through native Block Hooks during resolution, so canonical provider identity is proven from WordPress provenance while direct raw-source-versus-resolved content equivalence remains `NOT_PROVEN` instead of depending on private Core APIs.

WU-04 evidence is integration-level Automated Qualification Lab evidence only. WU-05 browser geometry, WU-06 browser/admin RTL/accessibility/security qualification, WU-07 mechanical Owner browser E2E plus human comprehension, production-host confirmation, and Production Qualification remain outside this claim.
