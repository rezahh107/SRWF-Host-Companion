# Security Policy

SRWF Host Companion is a project-specific WordPress host-integration plugin.

## Current status

V0 architecture remains approved and frozen. WU-01 is complete as the prerequisite for WU-02, and the WU-02 minimal runtime core is implemented on `main`. WU-03 and later work remain incomplete as applicable; Full Width geometry is `NOT_PROVEN`; browser/E2E and later qualification remain `NOT_RUN` / `NOT_PROVEN`. No production-qualified or production-supported release has been published.

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

## Supported versions

No production-supported release exists yet.

Do not infer support from source presence, branch names, or development version strings.
