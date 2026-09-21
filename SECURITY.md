# Security Policy

SRWF Host Companion is a project-specific WordPress host-integration plugin.

## Current status

V0 architecture remains approved and frozen. WU-01 through WU-07 mechanical qualification are implemented/qualified to their documented claim ceilings, the bounded Inbox Full Width amendment is implemented/qualified, and the personal/project-specific GitHub Release `v0.2.0` is published. The qualified target remains WordPress `7.1.1`, PHP `8.3.33`, and Twenty Twenty-Five `1.5`.

This published personal release is **not** a claim of `PRODUCTION_QUALIFIED_FOR_SRWF`. Human comprehension, direct production-host confirmation, complete WCAG 2.2 AA conformance, and unavailable real Gravity Forms / Orbital / GTB / Vazir-Vazirmatn integration remain explicitly unproven.

## Security baseline

Implementation must preserve the Mother Architecture security boundary:

- admin access requires an appropriate capability;
- mutating actions require authorization plus nonce protection;
- target page mutations require authority to edit the target page;
- page IDs and other inputs are untrusted and must be validated;
- output must be escaped for its rendering context;
- diagnostics must not expose credentials, cookies, nonces, tokens, student data, Gravity Forms entry values, uploaded-file contents, workflow assignments, or unnecessary PII;
- opening/rendering the settings page and read-only checks must not perform hidden state mutation or external side effects.

## Reporting

Do not include secrets, credentials, production student data, or other sensitive information in a public issue.

Use a private channel with the repository Owner for sensitive reports. If GitHub Private Vulnerability Reporting is enabled for this repository, it is an appropriate channel.

## Published version

Current personal GitHub release: `v0.2.0`.

Release metadata floor:

- WordPress `7.1+`;
- PHP `8.3+`.

The release is project-specific and does not create a broad third-party compatibility or production-support promise. Security maintenance and future release support remain at the repository Owner's discretion.
