# Príklady

Každý príklad je malý Composer projekt v [`examples/`](https://github.com/tomaj/omnipay-slsp/tree/master/examples) s kódom, ktorý vidíte na jeho stránke, a so súborom `expected-output.txt`. CI každý z nich nainštaluje proti kódu pull requestu a zlyhá, keď sa výstup zmení, takže to, čo tu čítate, knižnica naozaj robí. Bežia bez siete: stranu banky hrá pár riadkov PHP, ktoré podpisujú cez `openssl_encrypt()` (a príkazový riadok `openssl`), nezávisle od knižnice.

| Príklad | Čo ukazuje | Spustenie |
|---|---|---|
| [Presmerovanie na platbu](./purchase-redirect) | `purchase()`, každé pole podpísanej URL, konštanty, `getRedirectResponse()` | `php run.php` |
| [Návratová URL](./return-url) | `completePurchase()` v controlleri: zaplatené, odmietnuté, upravený výsledok, nižšia suma, iná objednávka, iná mena, iný špecifický symbol, bez parametrov, obnovenie stránky | `php run.php` |
| [Formáty sumy](./amount-formats) | ako sa sumy formátujú v `purchase()` a porovnávajú v `completePurchase()` | `php run.php` |
| [Podpisy cez openssl](./signatures) | `sign1` a `SIGN2` z knižnice aj z príkazového riadka `openssl`, 16- a 24-bajtové kľúče, neplatné kľúče | `php run.php` |
| [Celý priebeh s presmerovaním](./full-flow) | e-shop a falošná banka na `php -S`, skript ako prehliadač: platba, odmietnutie, upravená suma, podvrhnutý návrat | `bash e2e.sh` |

## Spustenie príkladu {#run-one}

```bash
git clone https://github.com/tomaj/omnipay-slsp.git
cd omnipay-slsp/examples/return-url
composer install
php run.php            # or: bash e2e.sh in examples/full-flow
```

`examples/ci/run.sh <name>` robí to isté čo CI: skopíruje príklad do dočasného adresára, nainštaluje ho proti vášmu checkoutu (Composer path repository) a porovná výstup s `expected-output.txt`.

Príklady používajú zdieľaný kľúč simulátora platieb (`Z3qY08EpvLlAAoMZdnyUdQ==`) a vymyslené účty. Vo vašom e-shope načítajte kľúč z premenných prostredia.
