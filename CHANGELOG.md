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
- repository hygiene and pull-request foundation.

### Status

```text
Architecture: APPROVED / FROZEN FOR V0 + OWNER-APPROVED QUALIFICATION-LAB AMENDMENT
WU-01: COMPLETE_FOR_WU02
WU-02: IMPLEMENTED_ON_MAIN
WU-03 Owner settings workflow: IMPLEMENTED_ON_MAIN / WORDPRESS_INTEGRATION_PROVEN
WU-04 diagnostics/drift: NOT_IMPLEMENTED
WU-05 Full Width geometry: NOT_PROVEN
WU-06 admin UX/security/RTL/accessibility qualification: NOT_RUN
WU-07 browser/E2E/release gate: NOT_RUN
Automated Qualification Lab: APPROVED_DIRECTION / NOT_YET_FULLY_IMPLEMENTED
Production qualification: NOT_PROVEN
Production release: NOT_PUBLISHED
```

PR #6 merged WU-03 to `main` as `18fd0b3959301d2bd1066d255a9eb3f1897c199e`. WU-03 evidence on the pinned disposable tuple (WordPress `7.1.1`, PHP `8.3.33`, Twenty Twenty-Five `1.5`) is integration-level only. Browser/E2E behavior, admin RTL/accessibility/comprehension, WU-04 diagnostics/drift, WU-05 geometry, and production qualification remain outside the proven claim.

The approved Automated Qualification Lab is an evidence strategy, not a production-simulator claim. It should extend/reuse the existing runtime-lab for deterministic fixtures, browser assertions where required, machine-readable evidence, and useful failure artifacts. Human comprehension and production-specific confirmation remain separate requirements, and unavailable real dependencies must remain `NOT_PROVEN` or `ENVIRONMENT_UNAVAILABLE` rather than synthetic PASS.
