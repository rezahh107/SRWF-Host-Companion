# SRWF Host Companion — Mother Architecture

**Repository:** `rezahh107/SRWF-Host-Companion`  
**Plugin Name:** `SRWF Host Companion`  
**Plugin Slug:** `srwf-host-companion`  
**Suggested Namespace:** `SRWF\HostCompanion`  
**Document:** `docs/architecture/MOTHER_ARCHITECTURE.md`  
**Document Version:** `0.5`  
**Date:** `2026-09-20`  
**Authority:** Mother Architecture  
**Owner Approval:** `APPROVED`  
**Supersedes:** `v0.4`  
**Status:** `ARCHITECTURE_FROZEN_FOR_V0_IMPLEMENTATION`  
**Primary Consumer:** SRWF  
**Initial Qualified Host:** Twenty Twenty-Five (TT25)  
**Architecture Style:** Small, project-specific, extensible WordPress host-integration plugin  
**Primary Operator:** Non-technical Owner / administrator  
**UI Baseline:** WordPress-native, Persian-first, self-guided, evidence-aware

## Change Summary from v0.4

- added an explicit Product Vision / Problem Origin so future agents understand why this plugin exists, not only what it must do;
- recorded the intended long-term role-based host-shell direction while preserving evidence-before-expansion;
- clarified that SRWF is an application hosted inside WordPress rather than a conventional content site;
- clarified the enduring ownership rule: this plugin owns where SRWF application content sits inside WordPress, not how that application content looks or behaves;
- preserved all previously approved V0 contracts, boundaries, security rules, and Release Gate requirements unchanged.

---

## 0. Product Vision / Problem Origin

SRWF is an application hosted inside WordPress, not a conventional content website.

Twenty Twenty-Five is intentionally retained as the standard host theme because the project does not want to own, fork, or maintain a custom WordPress theme merely to give SRWF application pages the host geometry they need. TT25 should continue to provide the normal site shell: header, footer, navigation, standard WordPress behavior, and the general block-theme environment.

The practical problem is that a general-purpose content theme may render application-style SRWF pages inside a content-width shell that is appropriate for articles but too narrow or otherwise unsuitable for an application surface. This can affect the public Registration experience and, if later proven by runtime evidence, may also affect operational surfaces such as Gravity Flow Inbox or Entry Detail.

The project does **not** want to solve that mismatch by:

- hacking or forking TT25;
- creating and maintaining a custom theme;
- turning GTB into a page-shell/layout owner;
- turning GPP into an SRWF host-integration plugin;
- rebuilding Gravity Forms or Gravity Flow surfaces;
- requiring the non-technical Owner to edit Site Editor internals or template markup manually.

The intended solution is a small, project-owned host-integration layer between WordPress/TT25 and SRWF application content.

The Owner should be able to say, from a simple WordPress admin surface:

> “This WordPress page has the SRWF Registration role. Apply and verify the correct SRWF host shell for it.”

The plugin then supplies and verifies the correct WordPress page shell for that semantic role while leaving the application content itself under its rightful owners.

The product direction is therefore:

```text
WordPress + TT25
        │
        ▼
SRWF Host Companion
        │
        ├── role: registration
        │      └── Full Width host shell
        │
        └── future proven roles
               └── role-specific host shells
        │
        ▼
Application content
├── Gravity Forms + GTB
└── Gravity Flow + GPP
```

V0 proves this model with one role only:

```text
registration
```

Future roles, including operational Gravity Flow surfaces, are extension points rather than assumed requirements. They may be admitted only when real runtime evidence demonstrates that a distinct host shell is necessary.

The enduring ownership statement is:

> **SRWF Host Companion owns where SRWF application content sits inside WordPress. It does not own how that application content itself looks or behaves.**

This Product Vision explains the architectural boundaries that follow. It does not expand V0 scope beyond the already approved Registration role.

---

## 1. Purpose

SRWF Host Companion is the project-specific WordPress host-integration layer for SRWF.

It exists to solve host-level problems that belong neither to the WordPress theme nor to GTB, GPP, Gravity Forms, Gravity Flow, PersianGravity, or Vazir.

Its first responsibility is deliberately small:

> Give the Owner a simple, reliable way to select the SRWF Registration page and apply a canonical Full Width WordPress Block Template to it without editing theme files, understanding template internals, or manually building templates in the Site Editor.

The plugin must be simple enough for a non-technical Owner to operate safely, while remaining structured enough to grow when future SRWF host-level requirements are proven.

---

## 2. Mental Model

Think of the WordPress site as a building.

- **TT25** owns the building.
- **SRWF Host Companion** owns the floor plan for SRWF rooms.
- **Gravity Forms** owns form behavior and data.
- **GTB** owns public registration presentation.
- **Gravity Flow** owns workflow behavior.
- **GPP** owns operational presentation.
- **PersianGravity** owns admitted Persian/Gravity ecosystem capabilities.
- **Vazir / Vazirmatn** owns approved frontend typography.

SRWF Host Companion answers:

> “Which WordPress page is the SRWF Registration page, and which SRWF host template should WordPress use for it?”

It must not answer:

> “What should a Gravity Forms radio button look like?”

or:

> “Who may approve a Gravity Flow step?”

---

## 3. Core Ownership Model

```text
WordPress Core
└── platform, block/template APIs, rendering lifecycle

Host Theme (initially TT25)
└── site shell, header/footer/navigation, block-theme behavior

SRWF Host Companion
├── Owner-facing setup page
├── role-based page mapping
├── canonical SRWF Registration template
├── safe explicit template assignment
├── runtime status / diagnostics
├── template drift detection
├── self-guided setup guidance
└── future bounded host-level capabilities

Gravity Forms
├── forms
├── data
├── validation
├── submission
└── conditional logic

GTB
└── public Registration presentation

Gravity Flow
├── workflow state
├── assignment
├── Inbox behavior
├── Entry Detail behavior
└── approval semantics

GPP
└── Gravity Forms / Gravity Flow operational presentation profiles

PersianGravity
└── admitted Persian/localization capabilities

Vazir / Vazirmatn
└── approved frontend typography
```

---

## 4. Non-Negotiable Boundaries

SRWF Host Companion MUST NOT become a second GTB, GPP, Gravity Forms, Gravity Flow, page builder, or replacement theme.

It MUST NOT own:

- Gravity Forms field styling;
- Gravity Forms validation;
- Gravity Forms submission behavior;
- Gravity Flow workflow logic;
- Gravity Flow permissions or assignments;
- GTB visual rules;
- GPP visual rules;
- PersianGravity localization ownership;
- Vazir/Vazirmatn font delivery;
- project business data;
- a custom workflow engine;
- a general-purpose WordPress utility collection;
- a parallel admin design system;
- a replacement theme.

---

## 5. Applicable Design / Governance Guidance

The project may selectively adopt materially relevant guidance from:

`rezahh107/Personal-Preference-Decision-Model`

Inspected source baseline:

```text
main @ 94ed79c9f8fe57357d2834ded5633d4767e4abe7
```

Materially adopted areas include:

- WordPress-native plugin admin UI;
- self-guided UX for a qualified but product-unfamiliar user;
- truthful semantic states;
- explicit action consequences;
- contextual diagnostics;
- progressive disclosure of technical detail;
- RTL/i18n/accessibility;
- scoped assets and side-effect-free rendering;
- exact-target qualification / claim ceiling;
- small coherent V0 before speculative extensibility.

This external repository is **design/governance guidance**, not a runtime dependency.

Current explicit SRWF project authority and Owner instruction outrank generic preference/default guidance.

The plugin MUST NOT depend on PPDM being present at runtime.

---

## 6. Target User Contract

The primary operator is a capable WordPress administrator who should **not** be required to understand:

- repository architecture;
- PHP classes;
- WordPress template hierarchy;
- template slugs;
- `theme.json`;
- `wp_template`;
- post meta;
- Site Editor storage rules;
- hashes or internal diagnostic codes.

The UI must support this comprehension chain where relevant:

```text
این قابلیت برای چیست؟
→ از کجا شروع کنم؟
→ این دکمه چه می‌کند؟
→ چه اتفاقی افتاد؟
→ آیا مشکلی وجود دارد؟
→ اگر بله، الان چه کار کنم؟
→ جزئیات فنی را اگر خواستم از کجا ببینم؟
```

Technical depth must be **available, not mandatory**.

---

## 7. Admin Information Architecture — V0

V0 is a single-screen plugin.

Therefore the preferred location is:

```text
Settings
└── SRWF Host
```

A top-level admin menu is NOT justified in V0.

The Plugins screen SHOULD also expose a direct:

```text
Settings
```

action link for SRWF Host Companion.

The page has one main recurring responsibility:

> Configure and verify the SRWF Registration page host template.

Do not create separate Overview, Diagnostics, Help, or Advanced pages in V0.

If additional recurring responsibilities later become real and independent, information architecture may evolve.

---

## 8. Owner Experience — Required in V0

The first-release admin screen is a product requirement, not optional polish.

### 8.1 First-run orientation

Before configuration, the page should explain in plain Persian, very briefly:

- this plugin makes the selected Registration page use the SRWF Full Width shell;
- it does not edit Gravity Forms fields;
- it does not redesign the form;
- it does not change student data;
- the Owner only needs to choose the Registration page and apply the template.

A blank `No data` experience is forbidden.

### 8.2 Registration Page selector

The Owner sees conceptually:

```text
صفحه ثبت‌نام
[ فرم ثبت‌نام ▼ ]
```

The plugin stores the selected Page ID under the `registration` role in a versioned configuration object.

### 8.3 One clear primary action

The preferred primary action is explicit about both consequences:

```text
[ ذخیره و اعمال قالب تمام‌عرض ]
```

Immediately before or beside the action, the UI should explain:

> این کار صفحه انتخاب‌شده را به قالب `SRWF — Registration Full Width` متصل می‌کند. محتوای صفحه و داده‌های فرم را تغییر نمی‌دهد.

This is a material behavior claim and must remain aligned with runtime behavior through test/evidence.

### 8.4 Read-only verification action

A secondary action may be:

```text
[ بررسی دوباره ]
```

This action MUST be read-only.

It may refresh diagnostics but MUST NOT:

- assign a template;
- save a different page;
- modify content;
- repair drift;
- call an external service.

`Check Again` and render/opening the settings screen are observability actions, not mutation paths.

---

## 9. Versioned Configuration Schema

The plugin MUST NOT store the Registration page as an isolated scalar option that makes future role expansion unnecessarily disruptive.

V0 uses a versioned, role-based configuration object.

Conceptual schema:

```php
[
    'schema_version' => 1,
    'roles' => [
        'registration' => [
            'page_id' => 123,
        ],
    ],
]
```

Rules:

- V0 exposes only the `registration` role in the UI.
- `registration` has exactly one `page_id` in schema v1.
- Future roles may be added under `roles` without changing the top-level storage model.
- A future role MAY support multiple pages only when real requirements prove that cardinality.
- Do not model `registration` as an array of page IDs merely for hypothetical future needs.
- Future schema changes MUST increment `schema_version` and define migration behavior.

This keeps the storage extensible without speculative complexity.

---

## 10. Source of Truth and Assignment Contract

Two different facts must not be confused.

### Fact A — Registration role

Source of truth:

```text
SRWF Host Companion configuration
→ roles.registration.page_id
```

### Fact B — Actual rendered template

Runtime state:

```text
WordPress page/template assignment
```

Fact B is derived state and must match Fact A after a successful explicit apply action.

The plugin MUST NOT maintain competing Registration-page mappings.

The settings page is the Owner-facing editor of Fact A; the stored configuration is the canonical project state.

---

## 11. Page Change Safety

Changing the selected Registration page can affect two pages:

- the newly selected page;
- the previously selected page.

V0 MUST NOT silently rewrite or restore the old page's template assignment merely because the Registration role changed.

On a page-role change, the UI should clearly state what will happen.

Default V0 behavior:

1. the newly selected page becomes the canonical Registration page;
2. the canonical SRWF template is explicitly assigned to that new page when the Owner confirms;
3. the previous page is not silently modified;
4. if the previous page still uses an SRWF template, this may be reported as informational evidence rather than automatically “fixed”.

Any future “restore previous page” action must be explicit and evidence-backed.

---

## 12. Page Validity Model

A stored Page ID is not automatically valid forever.

The plugin must detect at least these conditions:

```text
PAGE_VALID
PAGE_NOT_PUBLISHED
PAGE_INVALID
```

`PAGE_INVALID` may be refined internally as:

```text
PAGE_MISSING
PAGE_TRASHED
PAGE_TYPE_INVALID
```

Interpretation:

- `PAGE_VALID`: object exists, is a WordPress `page`, and is in an admissible state.
- `PAGE_NOT_PUBLISHED`: page exists and is a valid `page`, but is not currently publicly published.
- `PAGE_MISSING`: stored object no longer exists.
- `PAGE_TRASHED`: selected page is in Trash.
- `PAGE_TYPE_INVALID`: stored ID resolves to a different post type.

A draft or private page MUST NOT automatically be labeled “invalid” merely because it is not public.

Owner-facing copy should explain the practical consequence.

---

## 13. V0 Scope — Registration Only

V0 MUST register only one application template:

```text
srwf-host-companion//registration-full-width
```

Display title:

```text
SRWF — Registration Full Width
```

A separate Operational template is deliberately deferred.

It may be added only when runtime evidence from Gravity Flow Inbox / Entry Detail proves that the Registration shell is not appropriate.

This prevents speculative architecture.

---

## 14. Registration Template Contract

Conceptual structure:

```text
Host Header
↓
SRWF Registration Shell
    ↓
    Post Content
        ↓
        Gravity Forms + GTB
↓
Host Footer
```

Requirements:

- full-width capable;
- safe horizontal gutter on narrow screens;
- no global host-theme `contentSize` mutation;
- no generic form-control styling;
- no Gravity Forms markup replacement;
- no Elementor dependency;
- no frontend JavaScript requirement;
- host header/footer remain available unless a later decision explicitly changes that.

“Full Width” does NOT mean “touch the viewport edge.”

On mobile:

```text
| safe gutter | SRWF content | safe gutter |
```

The exact block markup must be proven on the target WordPress + TT25 runtime.

---

## 15. First Layout Prototype Hypothesis — H1

The first implementation should have a concrete starting point without turning that starting point into an architecture invariant.

### H1 — Initial Full Width hypothesis

```text
Outer shell:
constrained
+
safe horizontal gutter

Post Content:
per-block layout override
contentSize = 100%
wideSize = 100%
```

Intent:

- keep safe mobile gutters;
- remove the unwanted narrow TT25 content constraint only for the SRWF content region;
- avoid global `theme.json` / `contentSize` mutation;
- keep the change local to the SRWF template.

Status:

```text
HYPOTHESIS_ONLY
```

H1 is NOT authoritative if runtime evidence shows a cleaner or more native implementation.

The implementation may change while preserving the architectural contract:

```text
Full Width application shell
+
safe mobile gutter
+
no global layout mutation
```

---

## 16. WordPress Template Registration

Use native WordPress Block Template APIs.

For the supported WordPress generation, prefer:

```php
register_block_template()
```

Canonical identity:

```text
srwf-host-companion//registration-full-width
```

Each template definition must include:

- stable namespaced identifier;
- user-facing title;
- concise description;
- canonical block markup;
- supported post type: `page`;
- canonical version/evidence identity.

Template identifiers are API-like contracts and must not be renamed casually after production use.

---

## 17. Template Assignment Persistence Boundary

The architecture MUST NOT assume or hard-code an undocumented internal persistence representation for WordPress page-template assignment.

In particular, the Mother Architecture does NOT claim that the exact stored assignment value is necessarily:

```text
srwf-host-companion//registration-full-width
```

The architecture-level contract is only:

```text
Selected Registration Page
→ WordPress resolves the canonical SRWF Registration template
```

The exact persisted value, metadata key/value behavior, template resolution path, and source/origin representation are **runtime facts**.

They MUST be captured by integration tests on the qualified WordPress version before implementation claims depend on them.

---

## 18. WordPress-Native Admin UI Contract

V0 should use the smallest native WordPress admin architecture that satisfies the workflow.

Preferred baseline:

```text
PHP/native admin rendering
+
Options/Settings APIs where appropriate
+
progressive enhancement only where useful
```

React, SPA, a custom settings framework, or a parallel component/design system are NOT V0 requirements.

Use WordPress-native controls and patterns before custom UI.

Admin styling should feel like WordPress, not like an unrelated SaaS embedded inside `wp-admin`.

The admin page should use native WordPress typography by default. Vazir/Vazirmatn ownership concerns the SRWF frontend unless a separate project decision explicitly extends typography into wp-admin.

---

## 19. UI Language / Internationalization Contract

The qualified Owner experience is Persian-first.

Rules:

```text
Owner-facing primary language: Persian
Technical identifiers: English / LTR where appropriate
Text domain: srwf-host-companion
All user-facing strings: translatable
```

Persian strings MUST NOT be implemented as untranslatable hard-coded output.

WordPress i18n APIs must be used for:

- menu labels;
- headings;
- form labels;
- buttons;
- notices;
- statuses;
- diagnostics;
- help text;
- accessibility labels.

The project may initially ship Persian translations as the qualified Owner experience while preserving the ability to localize the plugin later.

---

## 20. Semantic Status Model

Internal diagnostic state should remain small and truthful.

Suggested V0 vocabulary:

```text
READY
NEEDS_SETUP
ATTENTION_REQUIRED
UNKNOWN
```

More specific machine states may exist internally, for example:

```text
CANONICAL
CUSTOMIZED_DB_OVERRIDE
THEME_OVERRIDE
MISSING_TEMPLATE
WRONG_PAGE_ASSIGNMENT
PAGE_NOT_PUBLISHED
PAGE_INVALID
UNQUALIFIED_HOST_THEME
```

The default Owner-facing UI should translate these into plain meaning.

Example:

```text
✅ آماده استفاده
⚠️ نیاز به بررسی
❌ تنظیمات ناقص
❓ وضعیت هنوز قابل تشخیص نیست
```

Color/icon must never be the only carrier of meaning.

`UNKNOWN` is not `FAILED`.

Absence of evidence must not be presented as certainty.

---

## 21. Status / Contextual Advisor

Diagnostics should not be a raw dump.

For any material problem, the UI should answer:

```text
وضعیت فعلی چیست؟
مشکل چیست؟
چرا مهم است؟
چه کاری باید انجام دهم؟
بعد از اصلاح چطور دوباره بررسی کنم؟
```

Conceptual healthy state:

```text
وضعیت SRWF Host

✅ صفحه ثبت‌نام انتخاب شده است
✅ قالب تمام‌عرض SRWF ثبت شده است
✅ صفحه به قالب درست متصل است
✅ تغییری که نسخه اصلی را override کند پیدا نشد
```

Conceptual problem state:

```text
⚠️ صفحه ثبت‌نام از قالب مورد انتظار استفاده نمی‌کند.

اثر:
ممکن است فرم دوباره با عرض محدود پوسته نمایش داده شود.

اقدام:
[ اعمال قالب تمام‌عرض ]

بعد از آن:
[ بررسی دوباره ]
```

This contextual advisor must derive from real state and MUST NOT invent routes or silently mutate configuration.

---

## 22. Progressive Disclosure / Advanced Details

The primary UI is practical, not technical.

Technical details may exist under a collapsed area such as:

```text
جزئیات فنی
```

It may include:

- plugin version;
- WordPress version;
- PHP version;
- active theme and version;
- selected Page ID;
- canonical template slug;
- resolved template/source;
- normalized fingerprint/hash;
- diagnostic codes.

Technical values such as IDs, hashes, slugs and versions must remain copyable and directionally isolated in Persian RTL UI.

Do NOT expose guessed WordPress persistence values as established facts.

---

## 23. Safe Diagnostic Report

A compact diagnostic report is useful for support and LLM-assisted troubleshooting.

V0 MAY provide:

```text
[ کپی گزارش فنی ]
```

The report MUST be privacy-safe and MUST NOT contain:

- Gravity Forms entry values;
- student names/IDs;
- uploaded files;
- workflow assignments;
- authentication data;
- nonces;
- cookies;
- credentials/tokens;
- raw sensitive payloads.

Useful report fields may include:

```text
SRWF Host Companion version
WordPress version
PHP version
active theme + version
Registration Page ID/title
expected template slug
resolved template state
page validity state
drift state
diagnostic code
timestamp
```

If clipboard behavior requires JavaScript, it must be admin-only, scoped to the plugin screen, and progressive enhancement. Frontend JavaScript remains zero-by-default.

---

## 24. Template Drift Model

Canonical source in Git does not automatically guarantee canonical runtime output.

WordPress may store a customized `wp_template` in the database or a theme may provide a competing template.

Possible states include:

```text
CANONICAL
CUSTOMIZED_DB_OVERRIDE
THEME_OVERRIDE
MISSING_TEMPLATE
WRONG_PAGE_ASSIGNMENT
PAGE_NOT_PUBLISHED
PAGE_INVALID
UNKNOWN
```

V0 must detect and report material drift.

V0 MUST NOT silently erase a customized template.

A future explicit action may offer:

```text
Restore Canonical Template
```

only after behavior is proven safe, consequence is explained, and Owner intent is explicitly confirmed.

---

## 25. Drift Detection Strategy

Drift detection must be tested against the qualified WordPress version.

Do not assume raw string equality is sufficient.

When comparing block-template markup, canonical normalization such as:

```text
parse_blocks()
↓
serialize_blocks()
```

should be considered before hashing/comparison where appropriate.

The implementation must verify how resolved template origin/source is exposed on the exact qualified WordPress runtime.

Do not invent undocumented source semantics.

Diagnostics should separate:

```text
Evidence
Interpretation
Recommended next action
```

Example:

```text
Evidence:
A database-customized template exists for this slug.

Interpretation:
The runtime template may differ from the canonical plugin definition.

Next action:
Review details or restore only through an explicit supported action.
```

Do not jump from evidence to an unsupported root-cause claim.

---

## 26. Render / Save / Check / Repair Separation

These operations are different and must remain visibly different:

```text
Open page
≠ Save selection
≠ Apply template
≠ Check status
≠ Repair drift
```

Opening/rendering the settings screen MUST be side-effect-free.

A read-only status check MUST NOT mutate state.

A future repair/reset MUST be a separate explicit action.

No remote network request, telemetry send, template mutation, or page mutation should occur merely because the Owner views the settings page.

---

## 27. Security / Authorization Contract

Security requirements apply separately to **viewing** and **mutating** the plugin state.

### 27.1 Viewing admin UI / diagnostics

Minimum V0 capability:

```text
manage_options
```

The settings screen and diagnostics must not render to a user who lacks the required capability.

### 27.2 Saving / applying configuration

A state-changing action MUST verify:

```text
manage_options
+
valid WordPress nonce
+
edit_post capability for the selected target Page
```

The exact capability helper may use WordPress meta-cap resolution, but the effective requirement is that the current user is authorized to edit the target page.

### 27.3 Page ID validation

Submitted page identifiers must be handled as untrusted input.

Required validation sequence conceptually includes:

```text
absint
→ object exists
→ post_type === 'page'
→ status classified
→ current user may edit target page
```

Invalid objects must not be silently coerced into another valid page.

### 27.4 Output handling

All output must be escaped for its rendering context.

All user-facing URLs, attributes, text and technical values must use appropriate WordPress escaping APIs.

Nonce validation does not replace capability checks.

Capability checks do not replace input validation.

---

## 28. Deactivation / Removal Behavior

The plugin must define safe behavior before production use.

Initial policy:

- do not delete pages;
- do not modify Gravity Forms data;
- do not modify Gravity Flow state;
- do not silently destroy Site Editor customizations;
- do not silently rewrite page content;
- do not silently “restore” templates during deactivation;
- preserve settings on ordinary deactivation.

The exact WordPress fallback behavior for a page assigned to a plugin-registered template after plugin deactivation MUST be tested on the qualified WordPress version and documented.

Until proven, that behavior is:

```text
NOT_PROVEN
```

Uninstall behavior must be explicit.

Persistent plugin settings must not be deleted silently unless an explicit uninstall-cleanup policy is approved.

---

## 29. Presentation Neutrality

The plugin MUST NOT introduce generic application-control styling.

Forbidden by default:

```css
input {}
select {}
textarea {}
button {}
label {}
fieldset {}
legend {}
form {}
.gform* {}
.gravity* {}
```

The plugin should ideally ship with:

```text
frontend application CSS ≈ 0
frontend JavaScript = 0
external frontend requests = 0
bundled fonts = 0
```

If shell CSS is required for safe gutters/layout, it must be:

- minimal;
- scoped;
- logical-property based;
- unrelated to form-control appearance.

---

## 30. Typography

Frontend typography remains owned by Vazir / Vazirmatn and the admitted SRWF typography stack.

SRWF Host Companion:

- MUST NOT bundle fonts;
- MUST NOT load Google Fonts;
- MUST NOT define a competing global frontend font family;
- MUST verify that the approved typography remains effective inside the Registration template.

For the plugin's own wp-admin page, native WordPress admin typography is the default unless a separate approved requirement says otherwise.

---

## 31. RTL / i18n / Accessibility

### i18n

All user-facing plugin strings must be translatable.

Text domain:

```text
srwf-host-companion
```

### RTL

RTL is a product requirement, not a patch.

Plugin-owned CSS should prefer logical properties:

```css
margin-inline-start
margin-inline-end
padding-inline-start
padding-inline-end
inset-inline-start
border-inline-start
text-align: start
```

Technical LTR values such as:

```text
IDs
hashes
template slugs
versions
URLs
diagnostic codes
```

must remain readable, isolated and copyable inside Persian RTL UI.

### Accessibility

Baseline:

```text
WCAG 2.2 AA
```

Relevant requirements include:

- keyboard-accessible actions;
- logical tab order;
- visible `:focus-visible`;
- semantic buttons/links/headings/labels;
- dynamic status announced appropriately where needed;
- no state conveyed by color alone;
- usable zoom/reflow;
- responsive admin layout.

---

## 32. Responsive Admin UX

The plugin settings screen must remain usable at:

- normal desktop wp-admin width;
- narrow admin layout;
- phone-like width where WordPress admin is accessible;
- zoom/reflow scenarios.

Actions must wrap cleanly.

Technical details must not force destructive horizontal overflow.

Breakpoints should follow actual content behavior rather than importing arbitrary values from another project.

---

## 33. Asset Scoping / Performance

Admin CSS/JS owned by SRWF Host Companion must load only on SRWF Host Companion admin surfaces.

Unrelated wp-admin screens should not receive plugin UI assets.

Public frontend should not receive admin assets.

Frontend assets should remain zero/minimal unless the Registration shell proves it needs scoped layout CSS.

No external telemetry or background network calls are required for V0.

Performance claims must be evidence-based rather than based only on source size.

---

## 34. Version Policy

Because V0 uses `register_block_template()`, the minimum WordPress version must not be lower than:

```text
WordPress 6.7
```

The project must distinguish:

```text
Minimum Supported WordPress
Qualified WordPress
Minimum PHP
Qualified PHP
Qualified Host Theme + version
```

### Initial policy

```text
Minimum WordPress: 6.7
Qualified WordPress: exact SRWF runtime version pinned during implementation

Preferred engineering PHP floor: 8.2+
Minimum PHP: TO_BE_QUALIFIED
Qualified PHP: exact production runtime pinned during implementation

Qualified Host: exact Twenty Twenty-Five version pinned during implementation
```

The actual production host PHP version MUST be checked before freezing the plugin's minimum PHP requirement.

Do not exclude otherwise viable hosting environments merely because `8.2` was used as an unverified design assumption.

Do not claim a version/theme combination is qualified until that exact target or an explicitly justified equivalent target has been exercised.

Version-sensitive WordPress facts must be re-verified from official target-version sources during implementation.

---

## 35. Host Theme Policy

The plugin is not conceptually tied to TT25 forever.

TT25 is the first qualified host.

Rules:

- use native WordPress APIs first;
- avoid hard-coded TT25 selectors;
- introduce a theme-specific adapter only when evidence proves it necessary;
- do not advertise another theme as compatible without testing it.

The settings page should show the detected host in practical language.

Example:

```text
پوسته فعال: Twenty Twenty-Five
وضعیت: ✅ آزمایش‌شده برای این نسخه
```

or:

```text
پوسته فعال: Example Theme
وضعیت: ⚠️ این ترکیب هنوز برای SRWF تأیید نشده است
```

An unqualified theme should not automatically break or disable the site.

Its status must remain truthful.

---

## 36. Minimal V0 Code Architecture

Avoid a large framework for the first release.

Suggested starting structure:

```text
SRWF-Host-Companion/
├── srwf-host-companion.php
├── src/
│   ├── TemplateRegistrar.php
│   ├── AdminSettings.php
│   └── TemplateDiagnostics.php
├── templates/
│   └── registration-full-width.html
├── assets/
│   └── admin/
│       ├── admin.css        # only if needed
│       └── admin.js         # only if needed
├── tests/
│   ├── unit/
│   ├── integration/
│   └── e2e/
├── docs/
│   └── architecture/
│       ├── MOTHER_ARCHITECTURE.md
│       └── PPDM_ADOPTION.md
├── README.md
└── AGENTS.md
```

Do not introduce `ServiceProvider`, `CapabilityRegistry`, multiple provider classes, adapter hierarchies, or registries before a real need exists.

When the plugin gains multiple independent capabilities, it may evolve toward a bounded modular architecture.

---

## 37. Extensibility Rule

The plugin is designed to grow, but growth is conditional.

A new feature may enter only if all are true:

1. it is SRWF-specific;
2. it belongs to the WordPress host/integration boundary;
3. no existing component already owns it;
4. real runtime evidence shows the need;
5. it can be isolated behind a clear contract;
6. it does not make the primary Owner workflow harder without material benefit.

Potential future modules:

```text
Operational Template
Explicit Canonical Restore
Host Compatibility Diagnostics
Deployment/Runtime Evidence
Site Editor Governance
Additional SRWF Page Roles
```

Forbidden future directions:

```text
Form Builder
Workflow Engine
Generic Design System
Elementor Replacement
Generic WordPress Toolbox
```

---

## 38. Evidence / Claim Ceiling

A test may only prove what it actually exercised.

Examples:

```text
Unit PASS
≠ browser layout PASS

Template registration PASS
≠ production page assignment PASS

Fixture-created state PASS
≠ Owner can reach that state through the real UI

1440px PASS
≠ 390px RTL PASS

Repository source review PASS
≠ target production runtime PASS
```

Qualification states should preserve truthful distinctions such as:

```text
PASS
FAIL
NOT_RUN
NOT_PROVEN
ENVIRONMENT_UNAVAILABLE
```

Do not promote a narrow proof into a broader production-readiness claim.

---

## 39. Runtime Validation Matrix

The first production qualification must include real browser/runtime testing.

Required frontend widths:

```text
320px
390px
430px
1440px
```

Required direction:

```text
RTL
```

Required Registration checks include:

- Gravity Forms native lifecycle;
- Orbital;
- GTB;
- text inputs;
- numeric inputs;
- radio cards;
- selects;
- GP Advanced Select where present;
- File Upload Pro where present;
- validation;
- required markers;
- conditional logic;
- submit;
- keyboard/focus;
- mobile overflow;
- safe gutters;
- approved frontend typography;
- header;
- footer;
- navigation;
- template assignment.

Required admin checks include:

- first-run clarity;
- page selection;
- primary action consequence;
- save/apply success;
- invalid/missing/trashed page states;
- draft/non-published page state;
- capability failure;
- target-page `edit_post` failure;
- nonce failure at relevant test level;
- read-only `Check Again`;
- healthy status;
- drift warning;
- `UNKNOWN` state behavior;
- responsive/narrow wp-admin;
- Persian RTL;
- technical LTR isolation;
- accessible keyboard/focus flow;
- diagnostic report privacy.

Operational surfaces:

```text
Gravity Flow Inbox
Entry Detail
GPP
```

remain regression targets for the overall SRWF site, but V0 does not create a separate Operational template until evidence proves it necessary.

---

## 40. Testing Layers

V0 must not rely only on unit tests.

### Layer A — Unit

Examples:

- template ID;
- configuration schema;
- option validation;
- schema migration helpers when introduced;
- normalized comparison helpers;
- status translation;
- privacy-safe diagnostic serialization.

### Layer B — WordPress Integration

Examples:

- template registration;
- actual page-template assignment behavior;
- exact persisted/resolved assignment facts;
- options persistence;
- authorization/nonce behavior;
- target-page edit capability;
- resolved template state;
- page validity;
- drift detection.

### Layer C — E2E / Browser

Exercise the real Owner path:

```text
Open Settings → SRWF Host
→ understand first-run state
→ select Registration page
→ apply
→ receive truthful result
→ open frontend Registration page
→ verify Full Width + safe gutter
→ return to settings
→ Check Again
```

Also exercise relevant failure/drift paths.

---

## 41. Comprehension Verification

Automated tests prove behavior; they do not prove that a non-technical Owner understands the interface.

For the material V0 workflow, an independent comprehension review should verify that a user can answer from the UI itself:

1. این افزونه برای چیست؟
2. چه صفحه‌ای را باید انتخاب کنم؟
3. دکمه اصلی چه کاری انجام می‌دهد؟
4. آیا محتوای فرم تغییر می‌کند؟
5. آیا وضعیت فعلی سالم است؟
6. اگر مشکل وجود دارد، باید چه کنم؟
7. «بررسی دوباره» چه کاری می‌کند؟
8. جزئیات فنی را از کجا ببینم یا کپی کنم؟

Because the actual Owner is explicitly non-technical and the setup workflow is the product's core value, an Owner walkthrough is strongly appropriate before declaring the admin UX complete.

If a second independent reviewer is unavailable, that limitation must be reported rather than calling self-review independent evidence.

---

## 42. Production Qualification / Release Gate

A build may exist before every production gate passes.

However, the plugin MUST NOT be labeled or released as **production-qualified for SRWF** while a required gate remains unresolved.

Required gate:

```text
[ ] Exact WordPress version recorded and qualified
[ ] Exact production PHP version recorded
[ ] Minimum PHP requirement justified and frozen
[ ] Exact TT25 version recorded and qualified
[ ] Canonical template registration PASS
[ ] Real page-template assignment behavior PASS
[ ] Internal persistence/resolution facts documented
[ ] Registration settings flow E2E PASS
[ ] Capability + nonce + target edit authorization PASS
[ ] PAGE_INVALID / PAGE_NOT_PUBLISHED behavior PASS
[ ] Template drift detection PASS
[ ] Deactivation fallback behavior proven/documented
[ ] 320px RTL frontend PASS
[ ] 390px RTL frontend PASS
[ ] 430px RTL frontend PASS
[ ] 1440px frontend PASS
[ ] Safe mobile gutter PASS
[ ] Gravity Forms + GTB regression PASS
[ ] Header/Footer/Navigation regression PASS
[ ] Vazir/Vazirmatn frontend authority PASS
[ ] Admin RTL + technical LTR PASS
[ ] Keyboard/focus/accessibility baseline PASS
[ ] Diagnostics privacy review PASS
[ ] Owner/self-guided comprehension review recorded
```

A required unresolved item remains one of:

```text
NOT_RUN
NOT_PROVEN
FAIL
ENVIRONMENT_UNAVAILABLE
```

and blocks the broader `PRODUCTION_QUALIFIED_FOR_SRWF` claim.

A narrower development/testing artifact may still be produced if clearly labeled as such.

---

## 43. Definition of Done — V0

V0 is not Done merely because the plugin activates or a template appears in a dropdown.

Done requires, within applicable scope:

- Owner can find the settings screen without documentation;
- purpose is clear on first run;
- Registration page can be selected;
- versioned configuration persists correctly;
- primary action says what it will do;
- applying the template is explicit;
- render/check actions are side-effect-free;
- authorization is correct;
- invalid/missing/trashed/non-published page states are truthful;
- runtime state is shown truthfully;
- drift/unknown are not mislabeled as success/failure;
- technical details are optional;
- diagnostic output is privacy-safe;
- exact WordPress/PHP/TT25 targets are recorded;
- Full Width works at the required viewport matrix;
- mobile gutters are preserved;
- GTB/Gravity Forms behavior remains intact;
- RTL and technical LTR are usable;
- keyboard/focus baseline passes;
- assets are scoped;
- unit/integration/E2E evidence exists;
- Owner/comprehension review is recorded;
- required Release Gate items pass before a production-qualified claim;
- any unproven claim remains explicitly `NOT_PROVEN`.

---

## 44. Development Order

Preferred order:

```text
1. Freeze V0 architecture
2. Verify exact target WordPress + TT25 + production PHP facts
3. Implement plugin bootstrap
4. Implement versioned configuration schema v1
5. Register one canonical Registration template
6. Implement simple native settings screen
7. Implement explicit save/apply flow
8. Implement authorization guards
9. Implement page-validity states
10. Implement read-only diagnostics
11. Validate H1 Full Width geometry
12. Replace H1 if runtime evidence proves a better implementation
13. Add drift evidence
14. Add privacy-safe copyable diagnostics if useful
15. E2E + RTL + accessibility + comprehension review
16. Execute Production Qualification / Release Gate
17. Freeze qualified baseline
```

Avoid:

- speculative abstraction;
- generic framework building;
- duplicate authority;
- hidden automatic mutation;
- Site Editor expertise as a normal Owner requirement;
- requiring the Owner to edit code;
- claiming Production readiness from static/source-only proof.

---

## 45. Architecture Invariants

### INV-01 — Host Integration Only
The plugin owns SRWF-specific host integration, not application behavior.

### INV-02 — Native WordPress First
Use native WordPress APIs/patterns before custom machinery.

### INV-03 — Owner-Friendly Operation
Normal use must be possible from a simple admin UI without code or Site Editor expertise.

### INV-04 — Versioned Role Configuration
Project page roles are stored in a versioned configuration schema; V0 exposes only one `registration.page_id`.

### INV-05 — One Registration Role Source
The stored `roles.registration.page_id` is the project-level source of truth for the Registration role.

### INV-06 — Explicit Mutation
Template assignment/repair occurs only through clear user action, never merely by rendering the screen.

### INV-07 — Read-Only Check
`Check Again` / diagnostics do not mutate runtime state.

### INV-08 — Presentation Neutrality
Do not style Gravity Forms / Gravity Flow controls.

### INV-09 — No Duplicate Ownership
GTB, GPP, Gravity Forms, Gravity Flow, PersianGravity and Vazir retain their existing responsibilities.

### INV-10 — Canonical Source + Runtime Evidence
Canonical definitions may live in Git, but runtime overrides must be detectable.

### INV-11 — No Silent Destructive Repair
Drift repair must never silently destroy Owner changes.

### INV-12 — Truthful State
Unknown/partial/unqualified state must not be converted into false PASS/FAIL certainty.

### INV-13 — No Persistence Guessing
Internal WordPress assignment storage/resolution details are runtime facts and must be proven on the qualified target.

### INV-14 — Frontend Zero-JS by Default
Frontend JavaScript requires a proven need. Admin progressive enhancement may be scoped where it materially helps.

### INV-15 — No Font Ownership
The plugin does not own font delivery.

### INV-16 — RTL-First
Plugin-owned layout/UI must be intrinsically RTL-safe and preserve technical LTR readability.

### INV-17 — Privacy-Safe Diagnostics
Support output must exclude student/business secrets and unnecessary PII.

### INV-18 — Evidence Before Expansion
New modules require runtime evidence.

### INV-19 — No Theme Replacement
The plugin complements the host theme; it must not evolve into an undocumented custom theme.

### INV-20 — Exact-Target Claim Ceiling
Qualification claims cannot exceed the scope of the evidence actually exercised.

### INV-21 — Release Gate
Production-qualified release claims require all applicable Release Gate items to pass.

### INV-22 — Self-Guided Core Workflow
The core setup workflow must remain understandable to the non-technical Owner without reading repository documentation.

---

## 46. Decision Summary

Selected direction:

```text
TT25
    ↓
SRWF Host Companion
    ├── Settings → SRWF Host
    ├── self-guided Owner setup
    ├── versioned role configuration
    │   └── registration.page_id
    ├── canonical Full Width template
    ├── explicit safe assignment
    ├── read-only verification
    ├── page-validity checks
    └── runtime/drift diagnostics
        ↓
GTB
    public application presentation
        ↓
Gravity Forms
    behavior and data
```

Operational template support remains an extension point rather than a V0 assumption.

The plugin is intentionally small in code but not hostile to non-technical users.

The Owner-facing UI is part of the architecture, not optional polish.

The goal is:

> Make the correct SRWF host configuration easy to find, easy to understand, explicit to apply, hard to misread, easy to verify, safe to troubleshoot, and straightforward to extend when real needs appear.

---

## 47. Freeze Statement

With Owner approval on `2026-09-20`, this document is the frozen V0 architecture baseline.

Implementation may choose or revise low-level mechanisms only when they remain inside the contracts and invariants above.

Any material change to:

- ownership boundaries;
- configuration source of truth;
- mutation behavior;
- V0 scope;
- security model;
- Registration role cardinality;
- production qualification gate;
- presentation ownership;
- or Owner-facing workflow

requires an explicit architecture amendment before implementation.
