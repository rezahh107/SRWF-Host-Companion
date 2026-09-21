# SRWF Host Companion — Personal GitHub Release Policy

**Repository:** `rezahh107/SRWF-Host-Companion`  
**Document:** `docs/architecture/PERSONAL_GITHUB_RELEASE_POLICY.md`  
**Date:** `2026-09-21`  
**Authority:** Owner-approved release-policy amendment  
**Status:** `APPROVED / OWNER_LOCKED`  
**Applies to:** V0 / `v0.1.0` and later personal GitHub releases unless superseded by the Owner

---

## 1. Distribution channel

SRWF Host Companion is a personal, project-specific plugin for SRWF.

The intended distribution channel is:

```text
GitHub Releases
```

WordPress.org publication is not a project destination and is not a release requirement.

A GitHub Release is a distribution event for the Owner's project. It is not, by itself, a claim of broad third-party compatibility or `PRODUCTION_QUALIFIED_FOR_SRWF` status.

---

## 2. Initial release identity

The first release is:

```text
v0.1.0
```

Plugin version metadata must match `0.1.0` in the release candidate.

---

## 3. License

The Owner selects:

```text
GPL-2.0-or-later
```

The plugin header and repository release materials must state this license consistently.

---

## 4. Supported / qualified runtime policy for v0.1.0

The Owner intentionally chooses a narrow first-release compatibility policy rather than claiming compatibility that has not been exercised.

Release metadata:

```text
Minimum WordPress: 7.1
Minimum PHP: 8.3
```

Qualified SRWF target tuple:

```text
WordPress: 7.1.1
PHP: 8.3.33
Host theme: Twenty Twenty-Five 1.5
```

Twenty Twenty-Five `1.5` is the qualified host for this release. Other themes are not advertised as qualified.

The product surface remains the frozen V0 SRWF Registration host-integration scope. The release does not advertise generic WordPress, Page Builder, multi-theme, or unrelated site compatibility.

---

## 5. Personal GitHub release gate vs Production Qualification

The project distinguishes two claims:

```text
PERSONAL_GITHUB_RELEASE_READY
```

and

```text
PRODUCTION_QUALIFIED_FOR_SRWF
```

They are not equivalent.

For the Owner's personal GitHub release, unresolved evidence that cannot currently be obtained because of time or unavailable real dependencies does not automatically block publishing `v0.1.0`, provided all of the following are true:

- WU-01 through WU-06 established evidence remains green on the release-candidate head;
- the mechanical WU-07 Owner journey is exercised in the Automated Qualification Lab with a real browser;
- release metadata/version/license/runtime minimums are internally consistent;
- the release notes preserve all unresolved evidence honestly;
- no unresolved `FAIL` exists in the exercised release-candidate automation;
- the build is not labeled `PRODUCTION_QUALIFIED_FOR_SRWF`.

This is an Owner-approved release-policy choice for a personal GitHub-distributed project. It does not convert missing evidence into PASS.

---

## 6. Evidence that may be automated for WU-07

The lab should automate the mechanical Owner journey where it can reproduce the behavior faithfully:

```text
login
→ reach Settings → SRWF Host
→ observe first-run guidance
→ select Registration page
→ explicit save/apply
→ observe truthful result
→ verify persisted canonical assignment
→ open the selected frontend Registration page
→ verify canonical SRWF host shell renders
→ return to settings
→ Check Again
→ verify the check remains read-only
```

This may support a bounded claim such as:

```text
WU07_MECHANICAL_OWNER_E2E_PASS_ON_PINNED_TARGET_TUPLE
```

It does not prove human comprehension.

---

## 7. Explicitly unresolved evidence allowed for personal GitHub release

The following may remain unresolved for `v0.1.0` when the Owner cannot practically provide the real environment/evidence now:

```text
human comprehension: NOT_RUN / NOT_PROVEN
production-host confirmation: NOT_PROVEN
complete WCAG 2.2 AA conformance: NOT_PROVEN
real Gravity Forms integration: ENVIRONMENT_UNAVAILABLE / NOT_PROVEN
real Orbital integration: ENVIRONMENT_UNAVAILABLE / NOT_PROVEN
real GTB integration: ENVIRONMENT_UNAVAILABLE / NOT_PROVEN
real Vazir/Vazirmatn integration: ENVIRONMENT_UNAVAILABLE / NOT_PROVEN
```

Release notes must not restate these as PASS.

Synthetic fixtures may exercise SRWF Host Companion's own host-level mechanics, but they must not be described as proof that proprietary or unavailable real dependencies were exercised.

---

## 8. Human comprehension policy

The Owner explicitly does not require a separate human-comprehension session as a blocker for the personal GitHub `v0.1.0` release because of current time constraints.

Automation may verify that the required guidance, labels, consequence text, results, and next actions are visible and mechanically reachable.

The project must still preserve:

```text
human comprehension = NOT_PROVEN
```

unless a real human comprehension review is later performed.

This Owner choice changes the personal GitHub release gate only. It does not authorize a false human-comprehension PASS.

---

## 9. Production confirmation policy

The Owner does not require direct production-host mutation/testing as a blocker for the personal GitHub `v0.1.0` release.

The exact production identity already recorded for SRWF remains useful target evidence, but disposable CI is not a production clone.

Until direct production confirmation exists:

```text
production-host confirmation = NOT_PROVEN
PRODUCTION_QUALIFIED_FOR_SRWF = NOT_PROVEN
```

---

## 10. Release notes claim ceiling

A `v0.1.0` GitHub Release may truthfully say that the plugin is the Owner's first SRWF Host Companion release and that its automated qualification passed on the pinned target tuple.

It must also disclose the important evidence ceiling:

- personal/project-specific GitHub release;
- qualified on the stated pinned tuple;
- no claim of broad WordPress/theme compatibility;
- unavailable real dependency regressions remain unproven;
- human comprehension remains unproven;
- production-host confirmation remains unproven;
- not labeled `PRODUCTION_QUALIFIED_FOR_SRWF`.

---

## 11. Supersession

This document is an Owner-approved release-policy amendment.

It does not rewrite the frozen V0 product mission or ownership boundaries.

Where earlier repository text treated an undeclared license as a release blocker, that blocker is resolved by the Owner's `GPL-2.0-or-later` decision.

Where earlier process language could be read as requiring every Production Qualification item before any GitHub artifact may be published, this amendment clarifies the distinction:

```text
personal GitHub release
≠
PRODUCTION_QUALIFIED_FOR_SRWF claim
```

The stronger Production Qualification claim remains unavailable while its required real-world evidence is unresolved.
