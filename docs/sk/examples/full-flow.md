# Celý priebeh s presmerovaním

E-shop a falošný endpoint SporoPay, každý na vstavanom serveri PHP, a skript, ktorý hrá prehliadač zákazníka. Celý priebeh s presmerovaním spustí na localhoste za pár sekúnd: bez banky, bez simulátora, bez peňazí.

**Ukazuje:** `purchase()->redirect()` v pokladni, `completePurchase()` na návratovej URL s `Request::createFromGlobals()`, `testHost` nasmerovaný na lokálny endpoint, čo banka urobí s upravenou sumou, podvrhnutý návrat.

```text
examples/full-flow/
├── shop/index.php    the shop: /checkout, /return, /orders
├── bank/index.php    the fake bank: checks sign1, redirects back with SIGN2
├── browser.php       the customer: GET the checkout, follow the redirects
└── e2e.sh            starts both servers and runs the scenarios
```

## Výstup {#output}

<<< @/../examples/full-flow/expected-output.txt{text}

## E-shop {#the-shop}

<<< @/../examples/full-flow/shop/index.php{php}

## Falošná banka {#the-fake-bank}

Robí to isté čo SporoPay (alebo simulátor platieb), len cez `openssl_encrypt()` namiesto knižnice. Voľba zákazníka v banke prichádza z `?choice=`.

<<< @/../examples/full-flow/bank/index.php{php}

## Prehliadač {#the-browser}

<<< @/../examples/full-flow/browser.php{php}

## Skript {#the-script}

<<< @/../examples/full-flow/e2e.sh{bash}

## Ručné spustenie {#run-it-by-hand}

```bash
cd examples/full-flow
composer install
SHOP_DATA=$(mktemp -d) BANK_URL=http://127.0.0.1:8001 php -S 127.0.0.1:8000 shop/index.php &
php -S 127.0.0.1:8001 bank/index.php &
open http://127.0.0.1:8000/checkout?order=2024000123   # add &choice=OK to the bank's URL to pay
```
