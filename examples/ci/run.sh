#!/usr/bin/env bash
# Runs one example against this checkout and compares its output with expected-output.txt:
#   examples/ci/run.sh purchase-redirect
# The example is copied to a temporary directory first, so the checkout stays untouched. An example
# runs with e2e.sh when it has one (it starts its own servers), otherwise with `php run.php`.
set -euo pipefail
examples="$(cd "$(dirname "$0")/.." && pwd)"
name="${1:?usage: $0 <example>}"
[[ -f "$examples/$name/expected-output.txt" ]] || { echo "fail: $examples/$name/expected-output.txt not found" >&2; exit 1; }
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
cp -R "$examples/$name" "$work/$name"
rm -rf "$work/$name/vendor" "$work/$name/composer.lock"
"$examples/ci/use-local-package.sh" "$work/$name"
cd "$work/$name"
if [[ -f e2e.sh ]]; then
    bash e2e.sh > "$work/output.txt"
else
    php run.php > "$work/output.txt"
fi
if ! diff -u expected-output.txt "$work/output.txt"; then
    echo "fail: the output of examples/$name differs from expected-output.txt" >&2
    exit 1
fi
echo "ok: examples/$name"
