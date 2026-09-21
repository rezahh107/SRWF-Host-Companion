#!/usr/bin/env python3
"""Verify the repository release contract against the Owner-approved policy.

This is qualification-only tooling. It does not participate in plugin runtime.
"""

from __future__ import annotations

import argparse
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path
from typing import Callable

POLICY_PATH = Path("docs/architecture/PERSONAL_GITHUB_RELEASE_POLICY.md")
PLUGIN_PATH = Path("srwf-host-companion.php")
README_PATH = Path("README.md")
AGENTS_PATH = Path("AGENTS.md")
CHANGELOG_PATH = Path("CHANGELOG.md")
PLAN_PATH = Path("docs/implementation/V0_IMPLEMENTATION_PLAN.md")
LICENSE_PATH = Path("LICENSE")
REQUIRED_PATHS = (
    POLICY_PATH,
    PLUGIN_PATH,
    README_PATH,
    AGENTS_PATH,
    CHANGELOG_PATH,
    PLAN_PATH,
    LICENSE_PATH,
)


class VerificationError(RuntimeError):
    pass


def read(root: Path, relative: Path) -> str:
    path = root / relative
    if not path.is_file():
        raise VerificationError(f"missing required file: {relative}")
    return path.read_text(encoding="utf-8")


def markdown_section(text: str, heading: str) -> str:
    start = text.find(heading)
    if start < 0:
        raise VerificationError(f"missing policy/document section: {heading}")
    body_start = start + len(heading)
    next_heading = re.search(r"^##\s+", text[body_start:], flags=re.MULTILINE)
    end = body_start + next_heading.start() if next_heading else len(text)
    return text[body_start:end]


def first_text_code_block(section: str, label: str) -> str:
    match = re.search(r"```text\s*\n([^\n`]+)\n```", section)
    if not match:
        raise VerificationError(f"missing text code block for {label}")
    return match.group(1).strip()


def parse_policy(policy: str) -> dict[str, str]:
    version = first_text_code_block(
        markdown_section(policy, "## 2. Initial release identity"), "release version"
    )
    license_id = first_text_code_block(
        markdown_section(policy, "## 3. License"), "license"
    )
    runtime = markdown_section(
        policy, "## 4. Supported / qualified runtime policy for v0.1.0"
    )
    wp = re.search(r"^Minimum WordPress:\s*(\S+)\s*$", runtime, re.MULTILINE)
    php = re.search(r"^Minimum PHP:\s*(\S+)\s*$", runtime, re.MULTILINE)
    if not wp or not php:
        raise VerificationError("release runtime minimums missing from policy")
    return {
        "release_version": version,
        "plugin_version": version.removeprefix("v"),
        "license": license_id,
        "wordpress": wp.group(1),
        "php": php.group(1),
    }


def parse_plugin_headers(plugin: str) -> dict[str, str]:
    wanted = {"Version", "Requires at least", "Requires PHP", "License"}
    result: dict[str, str] = {}
    for line in plugin.splitlines()[:40]:
        match = re.match(r"^\s*\*\s*([^:]+):\s*(.*?)\s*$", line)
        if match and match.group(1).strip() in wanted:
            result[match.group(1).strip()] = match.group(2).strip()
    missing = wanted.difference(result)
    if missing:
        raise VerificationError(
            "plugin header missing required fields: " + ", ".join(sorted(missing))
        )
    return result


def require_equal(errors: list[str], label: str, observed: str, expected: str) -> None:
    if observed != expected:
        errors.append(f"{label} mismatch: observed={observed!r} expected={expected!r}")


def validate(root: Path) -> list[str]:
    errors: list[str] = []
    try:
        policy_text = read(root, POLICY_PATH)
        facts = parse_policy(policy_text)
        plugin = parse_plugin_headers(read(root, PLUGIN_PATH))
        readme = read(root, README_PATH)
        agents = read(root, AGENTS_PATH)
        changelog = read(root, CHANGELOG_PATH)
        plan = read(root, PLAN_PATH)
        license_text = read(root, LICENSE_PATH)
    except VerificationError as exc:
        return [str(exc)]

    require_equal(errors, "plugin Version", plugin["Version"], facts["plugin_version"])
    require_equal(
        errors,
        "plugin Requires at least",
        plugin["Requires at least"],
        facts["wordpress"],
    )
    require_equal(errors, "plugin Requires PHP", plugin["Requires PHP"], facts["php"])
    require_equal(errors, "plugin License", plugin["License"], facts["license"])

    if facts["license"] in {"GPL-2.0-only", "GPL-2.0-or-later"}:
        if "GNU GENERAL PUBLIC LICENSE" not in license_text or "Version 2, June 1991" not in license_text:
            errors.append("LICENSE is not recognizable as GNU GPL version 2 text")
    else:
        errors.append(
            f"release-contract verifier has no LICENSE compatibility rule for policy license {facts['license']!r}"
        )

    policy_ref = str(POLICY_PATH)
    try:
        governing = markdown_section(readme, "## Governing documents")
    except VerificationError as exc:
        errors.append(str(exc))
        governing = ""
    if policy_ref not in governing:
        errors.append("README governing documents do not route release decisions to release policy")

    try:
        platform = markdown_section(readme, "## Platform policy")
    except VerificationError as exc:
        errors.append(str(exc))
        platform = ""
    readme_wp = re.search(r"^Release minimum WordPress:\s*(\S+)\s*$", platform, re.MULTILINE)
    readme_php = re.search(r"^Release minimum PHP:\s*(\S+)\s*$", platform, re.MULTILINE)
    if not readme_wp:
        errors.append("README release minimum WordPress is missing")
    else:
        require_equal(errors, "README release minimum WordPress", readme_wp.group(1), facts["wordpress"])
    if not readme_php:
        errors.append("README release minimum PHP is missing")
    else:
        require_equal(errors, "README release minimum PHP", readme_php.group(1), facts["php"])

    obsolete_readme_patterns = {
        "README still says no license is selected": r"license has \*\*not yet been selected\*\*",
        "README still carries the pre-release minimum WordPress": r"^Minimum WordPress:\s*6\.7\s*$",
        "README still carries an unfrozen release PHP state": r"^Minimum production PHP support policy:\s*NOT_YET_FROZEN\s*$",
        "README current WU-07 state is still NOT_RUN": r"^WU-07[^\n]*NOT_RUN\s*$",
        "README still describes WU-07 as open": r"WU-07[^\n]*remain(?:s)? open",
        "README still describes WU-06 by open-PR lifecycle": r"WU-06[^\n]*implemented in PR #10",
        "README still says no GitHub Release has been published": r"No GitHub Release has been published",
    }
    for label, pattern in obsolete_readme_patterns.items():
        if re.search(pattern, readme, re.MULTILINE | re.IGNORECASE):
            errors.append(label)

    try:
        agents_preflight = markdown_section(agents, "## 2. Mandatory preflight")
    except VerificationError as exc:
        errors.append(str(exc))
        agents_preflight = ""
    if policy_ref not in agents:
        errors.append("AGENTS does not register the release policy authority")
    if policy_ref not in agents_preflight:
        errors.append("AGENTS preflight does not require the release policy for release work")
    for stale in (
        "WU-06 admin browser RTL/security/accessibility qualification is implemented in PR #10",
        "WU-07 browser/E2E/comprehension/release-gate work remains open",
        "Do not publish a production release while the repository license is undeclared.",
        "a production release is requested before a license is explicitly selected",
        "no GitHub Release has been published",
    ):
        if stale in agents:
            errors.append(f"AGENTS contains obsolete current-state/release-policy wording: {stale}")

    try:
        status_section = markdown_section(changelog, "### Status")
    except VerificationError as exc:
        errors.append(str(exc))
        status_section = ""
    if re.search(r"^WU-07[^\n]*NOT_RUN\s*$", status_section, re.MULTILINE):
        errors.append("CHANGELOG current WU-07 status is still NOT_RUN")
    if "WU07_MECHANICAL_OWNER_E2E_PASS_ON_PINNED_TARGET_TUPLE" not in status_section:
        errors.append("CHANGELOG current status does not record WU-07 mechanical qualification")
    if "PRODUCTION_QUALIFIED_FOR_SRWF: NOT_PROVEN" not in status_section:
        errors.append("CHANGELOG no longer preserves PRODUCTION_QUALIFIED_FOR_SRWF = NOT_PROVEN")
    if "Personal GitHub release v0.1.0: PUBLISHED" not in status_section:
        errors.append("CHANGELOG no longer records personal GitHub release v0.1.0 = PUBLISHED")

    try:
        wu07 = markdown_section(plan, "## WU-07 — E2E / comprehension / release gate")
    except VerificationError as exc:
        errors.append(str(exc))
        wu07 = ""
    required_wu07_markers = (
        "WU07_MECHANICAL_OWNER_E2E_PASS_ON_PINNED_TARGET_TUPLE",
        "HUMAN_COMPREHENSION_NOT_PROVEN",
        "PRODUCTION_HOST_CONFIRMATION_NOT_PROVEN",
        "PRODUCTION_QUALIFIED_FOR_SRWF_NOT_PROVEN",
    )
    for marker in required_wu07_markers:
        if marker not in wu07:
            errors.append(f"V0 plan current WU-07 section missing marker: {marker}")

    for document_name, text in (("README", readme), ("AGENTS", agents), ("CHANGELOG", changelog)):
        if "PRODUCTION_QUALIFIED_FOR_SRWF" not in text or "NOT_PROVEN" not in text:
            errors.append(f"{document_name} does not preserve the production qualification claim ceiling")

    return errors


def run_verifier(script: Path, root: Path) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        [sys.executable, str(script), "--root", str(root)],
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        check=False,
    )


def copy_contract_state(source_root: Path, destination: Path) -> None:
    for relative in REQUIRED_PATHS:
        source = source_root / relative
        target = destination / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, target)


def self_test(root: Path) -> int:
    script = Path(__file__).resolve()
    baseline_errors = validate(root)
    if baseline_errors:
        print("Cannot falsify an already inconsistent baseline:", file=sys.stderr)
        for error in baseline_errors:
            print(f"- {error}", file=sys.stderr)
        return 1

    cases: list[tuple[str, Callable[[Path], None]]] = []

    def license_mismatch(temp: Path) -> None:
        path = temp / PLUGIN_PATH
        text = path.read_text(encoding="utf-8")
        text = re.sub(r"^(\s*\*\s*License:\s*).*$", r"\1MIT", text, count=1, flags=re.MULTILINE)
        path.write_text(text, encoding="utf-8")

    def runtime_mismatch(temp: Path) -> None:
        path = temp / PLUGIN_PATH
        text = path.read_text(encoding="utf-8")
        text = re.sub(
            r"^(\s*\*\s*Requires at least:\s*).*$",
            r"\g<1>0.0",
            text,
            count=1,
            flags=re.MULTILINE,
        )
        path.write_text(text, encoding="utf-8")

    def missing_authority(temp: Path) -> None:
        path = temp / README_PATH
        text = path.read_text(encoding="utf-8")
        text = text.replace(str(POLICY_PATH), "docs/architecture/REMOVED_RELEASE_POLICY.md")
        path.write_text(text, encoding="utf-8")

    def stale_wu07(temp: Path) -> None:
        path = temp / README_PATH
        text = path.read_text(encoding="utf-8")
        text, count = re.subn(
            r"^WU-07 mechanical Owner E2E:.*$",
            "WU-07 mechanical Owner E2E: NOT_RUN",
            text,
            count=1,
            flags=re.MULTILINE,
        )
        if count != 1:
            raise VerificationError("self-test could not locate durable README WU-07 status line")
        path.write_text(text, encoding="utf-8")

    cases.extend(
        (
            ("policy/header license mismatch", license_mismatch),
            ("policy/header runtime-minimum mismatch", runtime_mismatch),
            ("missing release-policy authority reference", missing_authority),
            ("stale current WU-07 NOT_RUN state", stale_wu07),
        )
    )

    for name, mutate in cases:
        with tempfile.TemporaryDirectory(prefix="srwf-release-contract-") as directory:
            temp = Path(directory)
            copy_contract_state(root, temp)
            mutate(temp)
            result = run_verifier(script, temp)
            if result.returncode == 0:
                print(f"FALSIFICATION FAIL: {name} was accepted", file=sys.stderr)
                return 1
            print(f"FALSIFICATION PASS: {name} rejected with exit {result.returncode}")

    print(
        "Release-contract verifier falsification PASS for license, runtime minimum, authority routing, and stale WU-07 state."
    )
    return 0


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--root", type=Path, default=Path("."))
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    root = args.root.resolve()

    if args.self_test:
        return self_test(root)

    errors = validate(root)
    if errors:
        print("RELEASE_CONTRACT_INCONSISTENT")
        for error in errors:
            print(f"- {error}")
        return 1

    facts = parse_policy(read(root, POLICY_PATH))
    print(
        "RELEASE_CONTRACT_PASS "
        f"version={facts['release_version']} license={facts['license']} "
        f"wordpress={facts['wordpress']} php={facts['php']}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
