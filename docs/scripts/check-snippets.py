"""The longer code samples come from examples/<name>/, which CI runs (examples.yml, tests/ExamplesTest.php).
Inline ```php blocks run in tests/DocsSnippetsTest.php.

- Every `<<< @/...` snippet import must point at an existing file.
- Every example's code (*.php, e2e.sh) and its expected-output.txt must be included by at least one page,
  and every example must have its own page in docs/examples/.
"""
import re
import sys
from pathlib import Path

docs = Path(__file__).resolve().parent.parent
examples = docs.parent / "examples"
errors = []
included = set()
for page in sorted(docs.rglob("*.md")):
    if "node_modules" in page.parts or ".vitepress" in page.parts:
        continue
    text = page.read_text(encoding="utf-8")
    for target in re.findall(r"^<<< @/(\S+?)(?:\{[^}]*\})?(?:\s|$)", text, re.M):
        path = (docs / target.split("#", 1)[0]).resolve()
        if not path.is_file():
            errors.append(f"{page.relative_to(docs)}: snippet {target} does not exist")
        included.add(path)

for example in sorted(p for p in examples.iterdir() if p.is_dir() and p.name != "ci"):
    if not (docs / "examples" / f"{example.name}.md").is_file():
        errors.append(f"examples/{example.name} has no page docs/examples/{example.name}.md")
    files = [f for f in example.rglob("*") if f.is_file() and "vendor" not in f.parts
             and (f.suffix == ".php" or f.name in ("e2e.sh", "expected-output.txt"))]
    for file in sorted(files):
        if file.resolve() not in included:
            errors.append(f"{file.relative_to(docs.parent)} is not included in any page")

for error in errors:
    print(error, file=sys.stderr)
if errors:
    sys.exit(1)
print(f"ok {len(included)} snippets imported from examples/")
