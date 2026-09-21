#!/usr/bin/env bash
set -Eeuo pipefail

repo_root="${1:-.}"
manifest_path="${2:-.github/release-manifest.json}"
output_dir="${3:-${RUNNER_TEMP:-/tmp}/srwf-release-artifacts}"

cd "$repo_root"

python3 -m json.tool "$manifest_path" >/dev/null
python3 tests/release/verify-release-contract.py --root .

readarray -t release_values < <(python3 - "$manifest_path" <<'PY'
import json
import re
import sys
from pathlib import Path

manifest_path = Path(sys.argv[1])
data = json.loads(manifest_path.read_text(encoding="utf-8"))
required = ("version", "plugin_version", "release_name", "notes_file", "asset_name")
missing = [key for key in required if not isinstance(data.get(key), str) or not data[key].strip()]
if missing:
    raise SystemExit("release manifest missing non-empty string fields: " + ", ".join(missing))
if not re.fullmatch(r"v\d+\.\d+\.\d+", data["version"]):
    raise SystemExit(f"unsupported release version format: {data['version']!r}")
if data["version"] != "v" + data["plugin_version"]:
    raise SystemExit("manifest version and plugin_version disagree")
expected_asset = f"srwf-host-companion-{data['plugin_version']}.zip"
if data["asset_name"] != expected_asset:
    raise SystemExit(f"asset_name must be {expected_asset!r}")
notes = Path(data["notes_file"])
if not notes.is_file():
    raise SystemExit(f"release notes file does not exist: {notes}")
for key in required:
    print(data[key])
PY
)

version="${release_values[0]}"
plugin_version="${release_values[1]}"
release_name="${release_values[2]}"
notes_file="${release_values[3]}"
asset_name="${release_values[4]}"

python3 - "$notes_file" <<'PY'
import sys
from pathlib import Path

text = Path(sys.argv[1]).read_text(encoding="utf-8")
required = (
    "WordPress: `7.1+`",
    "PHP: `8.3+`",
    "GPL-2.0-or-later",
    "PRODUCTION_QUALIFIED_FOR_SRWF",
    "NOT_PROVEN",
    "Reza Hashemi Hosseini",
)
missing = [marker for marker in required if marker not in text]
if missing:
    raise SystemExit("release notes missing required claim/metadata markers: " + ", ".join(missing))
PY

python3 - "$plugin_version" <<'PY'
import re
import sys
from pathlib import Path

expected = sys.argv[1]
text = Path("srwf-host-companion.php").read_text(encoding="utf-8")
match = re.search(r"^\s*\*\s*Version:\s*(\S+)\s*$", text, flags=re.MULTILINE)
if not match or match.group(1) != expected:
    raise SystemExit(f"plugin header version does not match manifest: expected={expected!r}")
PY

stage_dir="${RUNNER_TEMP:-/tmp}/srwf-host-companion-dist"
rm -rf "$stage_dir" "$output_dir"
mkdir -p "$stage_dir/srwf-host-companion" "$output_dir"

rsync -a \
  --exclude-from=.distignore \
  --exclude='.distignore' \
  ./ "$stage_dir/srwf-host-companion/"

for required_path in \
  srwf-host-companion.php \
  LICENSE \
  src/Configuration.php \
  src/TemplateRegistrar.php \
  src/PageTemplateAssignment.php \
  src/TemplateDiagnostics.php \
  src/AdminSettings.php \
  templates/registration-full-width.html; do
  test -f "$stage_dir/srwf-host-companion/$required_path"
done

for forbidden_path in \
  .git .github docs tests README.md CHANGELOG.md AGENTS.md SECURITY.md .distignore; do
  test ! -e "$stage_dir/srwf-host-companion/$forbidden_path"
done

zip_path="$output_dir/$asset_name"
(
  cd "$stage_dir"
  zip -qr "$zip_path" srwf-host-companion
)

unzip -tq "$zip_path" >/dev/null
root_entries="$(unzip -Z1 "$zip_path" | awk -F/ 'NF {print $1}' | sort -u)"
test "$root_entries" = "srwf-host-companion"

(
  cd "$output_dir"
  sha256sum "$asset_name" > SHA256SUMS.txt
)

printf 'RELEASE_PACKAGE_PASS version=%s plugin_version=%s asset=%s release_name=%s\n' \
  "$version" "$plugin_version" "$asset_name" "$release_name"
cat "$output_dir/SHA256SUMS.txt"
