"""Code examples come from examples/, which the test suite runs (tests/ExamplesTest.php).

- Every `<<< @/...` snippet import must point at an existing file.
- Every PHP file in examples/ must be included by at least one page.
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

for example in sorted(examples.glob("*.php")):
    if example.resolve() not in included:
        errors.append(f"examples/{example.name} is not included in any page")

for error in errors:
    print(error, file=sys.stderr)
if errors:
    sys.exit(1)
print(f"ok {len(included)} snippets imported from examples/")
