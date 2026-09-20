# V0 Implementation Plan

**Status:** `APPROVED_SEQUENCE / NOT_YET_EXECUTED`  
**Governing authority:** `docs/architecture/MOTHER_ARCHITECTURE.md`  
**Purpose:** break the frozen V0 architecture into bounded implementation work without reopening architecture selection.

This plan is operational sequencing, not a second architecture authority.

## WU-00 — Repository foundation

Scope:

- canonical Mother Architecture in repository;
- PPDM adoption record;
- README and AGENTS operating contract;
- inert WordPress plugin bootstrap;
- repository hygiene files;
- PR template and changelog.

Exit evidence:

```text
Repository documents resolve
Bootstrap PHP parses
No runtime feature claim
```

Current status after foundation commit:

```text
FOUNDATION_ESTABLISHED
```

## WU-01 — Target runtime fact capture

Before feature implementation, record the exact target facts needed by V0:

- WordPress version;
- production PHP version;
- Twenty Twenty-Five version;
- relevant WordPress Block Template behavior;
- exact page-template assignment persistence/resolution behavior;
- deactivation/fallback behavior that affects the contract.

Outputs should distinguish:

```text
DOCUMENTED
RUNTIME_PROVEN
NOT_PROVEN
```

Do not freeze minimum PHP until the production host is inspected.

## WU-02 — Minimal runtime core

Implement only:

- plugin bootstrap/load structure required by real code;
- versioned configuration schema v1;
- canonical Registration template registration;
- exact page-template assignment adapter based on WU-01 evidence.

Do not add admin UI complexity beyond what WU-03 owns.

Do not add Operational template support.

## WU-03 — Owner settings workflow

Implement:

- `Settings → SRWF Host`;
- first-run explanation;
- Registration Page selector;
- explicit `ذخیره و اعمال قالب تمام‌عرض` action;
- required authorization/capability/nonce checks;
- page validity classification;
- truthful save/apply result.

Normal use must not require Site Editor or code knowledge.

## WU-04 — Read-only diagnostics and drift

Implement:

- `Check Again` as read-only;
- expected-versus-resolved template state;
- `PAGE_VALID`, `PAGE_NOT_PUBLISHED`, `PAGE_INVALID` handling;
- canonical / DB override / theme override / missing / wrong assignment / unknown states as supported by real evidence;
- normalized block comparison if runtime evidence validates that approach;
- contextual next-action guidance;
- optional privacy-safe copyable diagnostic report.

No automatic repair in V0.

## WU-05 — Full Width geometry proof

Start with Mother Architecture hypothesis `H1`.

Validate on the real frontend:

```text
320px
390px
430px
1440px
RTL
```

Required proof includes:

- safe mobile gutters;
- no unwanted TT25 narrow constraint on the SRWF application region;
- header/footer/navigation remain correct;
- Gravity Forms + Orbital + GTB behavior remains intact;
- approved frontend typography remains effective.

If H1 fails, replace the implementation mechanism without reopening the architecture contract.

## WU-06 — Admin UX and security qualification

Exercise:

- first run;
- valid page;
- missing page;
- trashed page;
- non-published page;
- wrong type;
- insufficient capability;
- target page edit denial;
- nonce failure;
- successful apply;
- read-only Check Again;
- unknown/drift state;
- Persian RTL;
- technical LTR isolation;
- keyboard/focus;
- narrow admin layout;
- diagnostic privacy.

## WU-07 — E2E / comprehension / release gate

Run the real Owner path end to end.

Record:

- browser/runtime evidence;
- independent comprehension review where practical;
- Owner walkthrough;
- exact-target qualification state for every required release gate item.

Only when all applicable required gates pass may the build be called:

```text
PRODUCTION_QUALIFIED_FOR_SRWF
```

## Change-control rule

If any WU discovers that the frozen architecture cannot be implemented safely:

- stop the affected work;
- preserve the evidence;
- mark the affected claim `NOT_PROVEN`;
- propose the smallest architecture amendment;
- do not silently redesign the project inside an implementation PR.
