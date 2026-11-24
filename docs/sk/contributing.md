# Ako prispieť

Pull requesty sú vítané v [tomaj/omnipay-slsp](https://github.com/tomaj/omnipay-slsp). Pravidlá projektu sú v [CONTRIBUTING.md](https://github.com/tomaj/omnipay-slsp/blob/master/CONTRIBUTING.md); účasť sa riadi [kódexom správania](https://github.com/tomaj/omnipay-slsp/blob/master/CODE_OF_CONDUCT.md). Bezpečnostné problémy hláste súkromne, pozrite [SECURITY.md](https://github.com/tomaj/omnipay-slsp/blob/master/SECURITY.md).

## Zmeny, ktoré vieme zlúčiť {#changes-we-can-merge}

- [PER Coding Style](https://www.php-fig.org/per/coding-style/), kontrolovaný cez `composer phpcs` v `src`, `tests` a `examples`.
- Testy ku každej zmene správania. Zmena podpisovaného reťazca potrebuje testovací vektor vypočítaný nezávisle cez príkazový riadok openssl, pozrite [Podpisy](./guide/signatures#verify-with-openssl).
- Zmeny správania zdokumentované v `CHANGELOG.md` a na týchto stránkach.
- [Sémantické verzovanie](https://semver.org/): aktuálna rada je 4.x; zmeny, ktoré nie sú spätne kompatibilné, čakajú na ďalšiu hlavnú verziu.

## Kontroly {#checks}

```bash
composer install
composer check      # code style, PHPStan, Rector, tests
composer coverage   # HTML and Clover report in build/ (Xdebug or PCOV)
composer infection  # mutation testing
```

CI spúšťa PHP 8.4 (aj s najnižšími verziami závislostí), 8.5 a 8.6 (pokrytie aspoň 90 % a Infection) a nočné PHP 8.7, ktoré smie zlyhať.

## Dokumentácia {#documentation}

Tieto stránky sú v `docs/` (VitePress). Dlhšie ukážky kódu sa importujú z [`examples/`](https://github.com/tomaj/omnipay-slsp/tree/master/examples): každý príklad je Composer projekt so súborom `expected-output.txt`, ktorý spúšťa workflow `Examples` proti kódu pull requestu a `tests/SporoPay/ExamplesTest.php` na každej podporovanej verzii PHP. Každý ďalší blok ```` ```php ```` v README, `UPGRADE-4.0.md` a `docs/` beží v `tests/SporoPay/DocsSnippetsTest.php`; blok ```` ```text ```` za `<!-- snippet: output -->` je jeho očakávaný výstup a `<!-- snippet: skip -->` pred blokom ho preskočí (len pre kód, ktorý nemôže bežať samostatne, napríklad strana 3.x v kroku prechodu na novú verziu).

Každá stránka existuje po anglicky aj po slovensky: slovenským dvojčaťom `docs/<stránka>.md` je `docs/sk/<stránka>.md` s rovnakými ukážkami kódu a importmi príkladov. Zmena stránky mení obe verzie; `npm run docs:locales` zlyhá, keď stránke chýba dvojča. Úvodnú ilustráciu a diagramy generuje `scripts/illustrations.py` v oboch jazykoch.

```bash
cd docs
npm ci
npm run docs:dev    # preview on http://localhost:5173/omnipay-slsp/
npm run docs:links  # build and check every link
npm run docs:locales  # every page in English and Slovak
npm run images      # re-render og.png and the PNG icons after changing the SVGs (needs Chrome)
python3 scripts/check-svg.py
```

Jeden príklad spustíte rovnako ako CI: `examples/ci/run.sh return-url`.
