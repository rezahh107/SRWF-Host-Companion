from pathlib import Path

path = Path("docs/architecture/MOTHER_ARCHITECTURE.md")
text = path.read_text(encoding="utf-8")

marker = "## 0. Product Vision / Problem Origin"
if marker in text:
    print("Product Vision already present; no mutation required.")
    raise SystemExit(0)

text = text.replace("**Document Version:** `0.4`", "**Document Version:** `0.5`", 1)
text = text.replace("**Supersedes:** `v0.3`", "**Supersedes:** `v0.4`", 1)

summary_start = text.index("## Change Summary from v0.3")
summary_end = text.index("\n---\n", summary_start)
summary = """## Change Summary from v0.4

- added an explicit Product Vision / Problem Origin so future agents understand why this plugin exists, not only what it must do;
- recorded the intended long-term role-based host-shell direction while preserving evidence-before-expansion;
- clarified that SRWF is an application hosted inside WordPress rather than a conventional content site;
- clarified the enduring ownership rule: this plugin owns where SRWF application content sits inside WordPress, not how that application content looks or behaves;
- preserved all previously approved V0 contracts, boundaries, security rules, and Release Gate requirements unchanged.
"""
text = text[:summary_start] + summary + text[summary_end:]

section = """## 0. Product Vision / Problem Origin

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

"""

purpose = "## 1. Purpose\n"
if purpose not in text:
    raise RuntimeError("Purpose section anchor not found")

text = text.replace(purpose, section + purpose, 1)
path.write_text(text, encoding="utf-8")
print("Applied Mother Architecture v0.5 Product Vision amendment.")
