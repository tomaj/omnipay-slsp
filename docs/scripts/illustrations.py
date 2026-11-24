#!/usr/bin/env python3
"""Writes the logo, favicon, hero illustration, social card and diagram SVGs into docs/public.

Each drawing exists once, with colour tokens; the light and dark files only swap the palette. The hero and the
diagrams also exist in Slovak: the same drawing with the labels from STRINGS, written with a -sk suffix
(illustrations/hero-sk-light.svg, diagrams/flow-sk-dark.svg) for the pages under docs/sk/. Run it after a change,
then `node scripts/og-image.mjs` for the PNG icons, og.png and og-sk.png (or `npm run images` for both).

The omnipay-* packages and payment-simulator share the mark: a rounded square in the bank's primary colour with a
payment card in its accent colour, a magnetic stripe and a check mark cut out of it. Slovenská sporiteľňa is blue and
red.
"""

from __future__ import annotations

from pathlib import Path

PUBLIC = Path(__file__).resolve().parents[1] / "public"
NAME = "omnipay-slsp"

# The mark of the omnipay family: square = primary, card = accent, stripe and marks = primary.
MARK = """<rect x="2" y="2" width="60" height="60" rx="14" fill="{square}"/>
  <rect x="11" y="17" width="42" height="30" rx="5" fill="{card}"/>
  <rect x="11" y="23" width="42" height="6" fill="{square}"/>
  <path d="M17 39h11" fill="none" stroke="{square}" stroke-width="3.5" stroke-linecap="round"/>
  <path d="M37 39.5l4.5 4 8-9" fill="none" stroke="{square}" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>"""

LIGHT = {
    "square": "#1f5fd6", "card": "#e0242f",
    "ink": "#18181b", "muted": "#52525b", "panel": "#ffffff", "line": "#d4d4d8", "bar": "#f4f4f5",
    "accent": "#e0242f", "on_accent": "#ffffff", "bank": "#1f5fd6", "on_bank": "#ffffff", "field": "#f1f5fd",
    "ok": "#15803d", "ok_soft": "#ecfdf3", "arrow": "#1f5fd6", "bg": "#f3f7fe",
}
DARK = {
    "square": "#e0242f", "card": "#1f5fd6",
    "ink": "#f4f4f5", "muted": "#a1a1aa", "panel": "#1e2330", "line": "#3a4254", "bar": "#272d3c",
    "accent": "#e0242f", "on_accent": "#ffffff", "bank": "#2870ed", "on_bank": "#ffffff", "field": "#272d3c",
    "ok": "#4ade80", "ok_soft": "#11261a", "arrow": "#8dbbff", "bg": "#161a22",
}

FONT = "font-family=\"Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif\""
MONO = "font-family=\"ui-monospace, SFMono-Regular, Menlo, Consolas, monospace\""


def logo(c: dict[str, str]) -> str:
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" role="img" aria-label="{NAME}">
  <title>{NAME}</title>
  {MARK.format(**c)}
</svg>
"""


def check(x: float, y: float, color: str, width: float = 2.5) -> str:
    return f'<path d="M{x} {y}l4 4 8-9" fill="none" stroke="{color}" stroke-width="{width}" stroke-linecap="round" stroke-linejoin="round"/>'


def arrow_marker(c: dict[str, str]) -> str:
    return f"""<marker id="arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
      <path d="M0 0L10 5L0 10z" fill="{c['arrow']}"/>
    </marker>"""


# Labels per language. English files keep their names; Slovak files get a -sk suffix.
STRINGS: dict[str, dict[str, str]] = {
    "en": {
        "hero_title": "SporoPay payment flow",
        "hero_desc": "The shop redirects the customer to SporoPay with a signed URL, the bank sends the customer back with a signed result, and completePurchase() verifies the signature, the variable symbol and the amount before the order is paid.",
        "order": "Order 2024000123",
        "amount": "10.00 €",
        "pay": "Pay with SporoPay",
        "pay_size": "15",
        "signed_redirect": "signed redirect",
        "confirm": "Confirm payment",
        "verified": "SIGN2, vs and suma verified",
        "paid": "order 2024000123 paid",
    },
    "sk": {
        "hero_title": "Platba cez SporoPay",
        "hero_desc": "E-shop presmeruje zákazníka do SporoPay s podpísanou URL, banka ho vráti späť s podpísaným výsledkom a completePurchase() overí podpis, variabilný symbol a sumu skôr, než sa objednávka označí ako zaplatená.",
        "order": "Objednávka 2024000123",
        "amount": "10,00 €",
        "pay": "Zaplatiť cez SporoPay",
        "pay_size": "13.5",
        "signed_redirect": "podpísaná URL",
        "confirm": "Potvrdiť platbu",
        "verified": "SIGN2, vs a suma overené",
        "paid": "objednávka zaplatená",
    },
}


def hero(c: dict[str, str], t: dict[str, str] = STRINGS["en"]) -> str:
    """The flow: the shop redirects with a signed URL, the bank sends the customer back, completePurchase() verifies."""
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 520 440" width="520" height="440" role="img" aria-labelledby="t d">
  <title id="t">{t['hero_title']}</title>
  <desc id="d">{t['hero_desc']}</desc>
  <defs>
    {arrow_marker(c)}
  </defs>

  <!-- the shop -->
  <rect x="16" y="20" width="232" height="176" rx="14" fill="{c['panel']}" stroke="{c['line']}" stroke-width="1.5"/>
  <path d="M16 34a14 14 0 0 1 14-14h204a14 14 0 0 1 14 14v12H16z" fill="{c['bar']}"/>
  <circle cx="34" cy="34" r="4" fill="{c['line']}"/><circle cx="48" cy="34" r="4" fill="{c['line']}"/><circle cx="62" cy="34" r="4" fill="{c['line']}"/>
  <text x="80" y="38" {FONT} font-size="12" fill="{c['muted']}">shop.example/checkout</text>
  <text x="36" y="78" {FONT} font-size="14" fill="{c['muted']}">{t['order']}</text>
  <text x="36" y="112" {FONT} font-size="28" font-weight="700" fill="{c['ink']}">{t['amount']}</text>
  <rect x="36" y="134" width="176" height="40" rx="9" fill="{c['accent']}"/>
  <text x="124" y="159" {FONT} font-size="{t['pay_size']}" font-weight="700" fill="{c['on_accent']}" text-anchor="middle">{t['pay']}</text>

  <!-- signed redirect -->
  <path d="M252 72C350 72 392 78 392 112" fill="none" stroke="{c['arrow']}" stroke-width="2.5" stroke-dasharray="6 5" marker-end="url(#arrow)"/>
  <text x="268" y="58" {MONO} font-size="12" fill="{c['ink']}">purchase()</text>
  <text x="268" y="96" {FONT} font-size="11" fill="{c['muted']}">{t['signed_redirect']}</text>

  <!-- internet banking -->
  <rect x="272" y="120" width="232" height="190" rx="14" fill="{c['panel']}" stroke="{c['line']}" stroke-width="1.5"/>
  <path d="M272 134a14 14 0 0 1 14-14h204a14 14 0 0 1 14 14v26H272z" fill="{c['bank']}"/>
  <text x="290" y="146" {FONT} font-size="14" font-weight="700" fill="{c['on_bank']}">SporoPay</text>
  <rect x="290" y="170" width="196" height="84" rx="9" fill="{c['field']}"/>
  <text x="302" y="190" {MONO} font-size="11" fill="{c['muted']}">suma</text><text x="474" y="190" {MONO} font-size="11" fill="{c['ink']}" text-anchor="end">10.00 EUR</text>
  <text x="302" y="208" {MONO} font-size="11" fill="{c['muted']}">vs</text><text x="474" y="208" {MONO} font-size="11" fill="{c['ink']}" text-anchor="end">2024000123</text>
  <text x="302" y="226" {MONO} font-size="11" fill="{c['muted']}">ss</text><text x="474" y="226" {MONO} font-size="11" fill="{c['ink']}" text-anchor="end">0000000001</text>
  <text x="302" y="244" {MONO} font-size="11" fill="{c['muted']}">sign1</text>
  {check(458, 236, c['ok'], 2.2)}
  <rect x="290" y="264" width="196" height="32" rx="8" fill="{c['ink']}"/>
  <text x="388" y="285" {FONT} font-size="13" font-weight="600" fill="{c['panel']}" text-anchor="middle">{t['confirm']}</text>

  <!-- signed return -->
  <path d="M392 314C392 364 352 370 272 370" fill="none" stroke="{c['arrow']}" stroke-width="2.5" stroke-dasharray="6 5" marker-end="url(#arrow)"/>
  <text x="404" y="346" {MONO} font-size="12" fill="{c['ink']}">result=OK</text>
  <text x="404" y="364" {FONT} font-size="11" fill="{c['muted']}">SIGN2</text>

  <!-- verification -->
  <rect x="16" y="318" width="250" height="104" rx="14" fill="{c['ok_soft']}" stroke="{c['ok']}" stroke-width="1.5"/>
  <circle cx="56" cy="370" r="22" fill="{c['ok']}"/>
  {check(46, 370, c['ok_soft'], 3.5)}
  <text x="92" y="354" {MONO} font-size="13" font-weight="700" fill="{c['ink']}">completePurchase()</text>
  <text x="92" y="378" {FONT} font-size="12" fill="{c['muted']}">{t['verified']}</text>
  <text x="92" y="402" {FONT} font-size="13" font-weight="700" fill="{c['ok']}">{t['paid']}</text>
</svg>
"""


# The social card tagline per language: og.svg (English) and og-sk.svg (pages under docs/sk/).
OG = {
    "en": {"label": "SporoPay for Omnipay", "line1": "SporoPay for Omnipay:", "line2": "3DES-signed payments, verified returns"},
    "sk": {"label": "SporoPay pre Omnipay", "line1": "SporoPay pre Omnipay:", "line2": "platby podpísané 3DES, overené návraty"},
}


def og(c: dict[str, str], lang: str = "en") -> str:
    """The 1200 × 630 social card, in English or Slovak."""
    mark = MARK.format(**c)
    t = OG[lang]
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630" role="img" aria-label="{NAME}: {t['label']}">
  <rect width="1200" height="630" fill="{c['bg']}"/>
  <rect x="0" y="0" width="1200" height="14" fill="{c['square']}"/>
  <rect x="0" y="14" width="1200" height="6" fill="{c['accent']}"/>
  <g transform="translate(80 110) scale(2.6)">
  {mark}
  </g>
  <text x="80" y="370" {FONT} font-size="84" font-weight="800" fill="{c['ink']}">{NAME}</text>
  <text x="80" y="432" {FONT} font-size="34" fill="{c['muted']}">{t['line1']}</text>
  <text x="80" y="476" {FONT} font-size="34" fill="{c['muted']}">{t['line2']}</text>
  <text x="80" y="566" {MONO} font-size="26" fill="{c['ink']}">composer require tomaj/omnipay-slsp</text>
  <g transform="translate(700 70) scale(0.95)">
    {hero(c, STRINGS[lang]).split('</defs>', 1)[1].rsplit('</svg>', 1)[0]}
  </g>
  <defs>
    {arrow_marker(c)}
  </defs>
</svg>
"""


def sequence(c: dict[str, str], title: str, desc: str, lanes: list[str], steps: list[tuple]) -> str:
    """A sequence diagram. A step is (from, to, label, note) for a message or (lane, None, label, note) for an action."""
    width = 760
    gap = width / len(lanes)
    xs = [gap / 2 + i * gap for i in range(len(lanes))]
    top = 76
    height = top + 30 + 58 * len(steps)
    out = [f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {width} {height}" width="{width}" height="{height}" role="img" aria-labelledby="t d">
  <title id="t">{title}</title>
  <desc id="d">{desc}</desc>
  <defs>
    <marker id="arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
      <path d="M0 0L10 5L0 10z" fill="{c['arrow']}"/>
    </marker>
  </defs>"""]
    for x, lane in zip(xs, lanes):
        out.append(f'  <rect x="{x - 105:.0f}" y="12" width="210" height="44" rx="10" fill="{c["panel"]}" stroke="{c["line"]}" stroke-width="1.5"/>')
        out.append(f'  <text x="{x:.0f}" y="40" {FONT} font-size="15" font-weight="700" fill="{c["ink"]}" text-anchor="middle">{lane}</text>')
        out.append(f'  <path d="M{x:.0f} 56V{height - 10}" stroke="{c["line"]}" stroke-width="1.5" stroke-dasharray="4 5"/>')
    y = top
    for number, (src, dst, label, note) in enumerate(steps, 1):
        y += 58
        if dst is None:
            x = xs[src]
            out.append(f'  <rect x="{x - 112:.0f}" y="{y - 40}" width="224" height="48" rx="9" fill="{c["ok_soft"]}" stroke="{c["ok"]}" stroke-width="1.3"/>')
            out.append(f'  <text x="{x:.0f}" y="{y - 20}" {MONO} font-size="12.5" font-weight="700" fill="{c["ink"]}" text-anchor="middle">{number}. {label}</text>')
            out.append(f'  <text x="{x:.0f}" y="{y - 3}" {FONT} font-size="11.5" fill="{c["muted"]}" text-anchor="middle">{note}</text>')
            continue
        x1, x2 = xs[src], xs[dst]
        direction = 1 if x2 > x1 else -1
        out.append(f'  <path d="M{x1 + direction * 4:.0f} {y}H{x2 - direction * 6:.0f}" stroke="{c["arrow"]}" stroke-width="2.2" marker-end="url(#arrow)"/>')
        mid = (x1 + x2) / 2
        out.append(f'  <text x="{mid:.0f}" y="{y - 24}" {MONO} font-size="12.5" font-weight="700" fill="{c["ink"]}" text-anchor="middle">{number}. {label}</text>')
        out.append(f'  <text x="{mid:.0f}" y="{y - 8}" {FONT} font-size="11.5" fill="{c["muted"]}" text-anchor="middle">{note}</text>')
    out.append("</svg>\n")
    return "\n".join(out)


DIAGRAMS: dict[str, dict[str, tuple]] = {"en": {
    "flow": (
        "SporoPay payment flow",
        "The customer pays in the shop, purchase() redirects the browser to SporoPay with the signed fields in the query string, the customer confirms the payment in internet banking, SporoPay redirects the browser back to the return URL with every field signed by SIGN2, and completePurchase() verifies the signature, the variable symbol, the amount and the currency before the shop marks the order paid.",
        ["Customer's browser", "Your shop", "SporoPay"],
        [
            (0, 1, "Pay 10.00 €", "the order gets vs 2024000123"),
            (1, 0, "302 to SporoPay", "purchase(): pu_* suma vs … sign1"),
            (0, 2, "GET epayment.xml?…", "the signed query string"),
            (2, None, "the customer pays", "internet banking: sign in, confirm"),
            (2, 0, "302 to url", "u_* pu_* suma mena vs ss … result real SIGN2"),
            (0, 1, "GET /return?…", "the bank's signed answer"),
            (1, None, "completePurchase()", "SIGN2, vs, suma, then isSuccessful()"),
        ],
    ),
}, "sk": {
    "flow": (
        "Priebeh platby cez SporoPay",
        "Zákazník zaplatí v e-shope, purchase() presmeruje prehliadač do SporoPay s podpísanými poľami v query stringu, zákazník potvrdí platbu v internet bankingu, SporoPay presmeruje prehliadač späť na návratovú URL so všetkými poľami podpísanými cez SIGN2 a completePurchase() overí podpis, variabilný symbol, sumu a menu skôr, než e-shop označí objednávku ako zaplatenú.",
        ["Prehliadač zákazníka", "Váš e-shop", "SporoPay"],
        [
            (0, 1, "Zaplatiť 10,00 €", "objednávka dostane vs 2024000123"),
            (1, 0, "302 na SporoPay", "purchase(): pu_* suma vs … sign1"),
            (0, 2, "GET epayment.xml?…", "podpísaný query string"),
            (2, None, "zákazník zaplatí", "prihlási sa a potvrdí platbu"),
            (2, 0, "302 na url", "u_* pu_* suma mena vs ss … result real SIGN2"),
            (0, 1, "GET /return?…", "podpísaná odpoveď banky"),
            (1, None, "completePurchase()", "overí SIGN2, vs, sumu, menu a ss"),
        ],
    ),
}}


def write(path: Path, text: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(text, encoding="utf-8")
    print(f"wrote {path.relative_to(PUBLIC.parent)}")


def main() -> None:
    write(PUBLIC / "logo.svg", logo(LIGHT))
    write(PUBLIC / "logo-dark.svg", logo(DARK))
    write(PUBLIC / "favicon.svg", logo(LIGHT))
    write(PUBLIC / "og.svg", og(LIGHT))
    write(PUBLIC / "og-sk.svg", og(LIGHT, "sk"))
    for lang, strings in STRINGS.items():
        suffix = "" if lang == "en" else f"-{lang}"
        write(PUBLIC / "illustrations" / f"hero{suffix}-light.svg", hero(LIGHT, strings))
        write(PUBLIC / "illustrations" / f"hero{suffix}-dark.svg", hero(DARK, strings))
        for name, (title, desc, lanes, steps) in DIAGRAMS[lang].items():
            write(PUBLIC / "diagrams" / f"{name}{suffix}-light.svg", sequence(LIGHT, title, desc, lanes, steps))
            write(PUBLIC / "diagrams" / f"{name}{suffix}-dark.svg", sequence(DARK, title, desc, lanes, steps))


if __name__ == "__main__":
    main()
