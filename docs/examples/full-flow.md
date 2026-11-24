# Full redirect flow

A shop and a fake SporoPay endpoint, each on PHP's built-in server, and a script that plays the customer's browser. It runs the whole redirect flow on localhost in a few seconds: no bank, no simulator, no money.

**Shows:** `purchase()->redirect()` in a checkout, `completePurchase()` on the return URL with `Request::createFromGlobals()`, `testHost` pointing at a local endpoint, what the bank does with an edited amount, a forged return.

```text
examples/full-flow/
├── shop/index.php    the shop: /checkout, /return, /orders
├── bank/index.php    the fake bank: checks sign1, redirects back with SIGN2
├── browser.php       the customer: GET the checkout, follow the redirects
└── e2e.sh            starts both servers and runs the scenarios
```

## Output

<<< @/../examples/full-flow/expected-output.txt{text}

## The shop

<<< @/../examples/full-flow/shop/index.php{php}

## The fake bank

It does what SporoPay (or the payment simulator) does, with `openssl_encrypt()` instead of the library. The customer's choice in the bank comes from `?choice=`.

<<< @/../examples/full-flow/bank/index.php{php}

## The browser

<<< @/../examples/full-flow/browser.php{php}

## The script

<<< @/../examples/full-flow/e2e.sh{bash}

## Run it by hand

```bash
cd examples/full-flow
composer install
SHOP_DATA=$(mktemp -d) BANK_URL=http://127.0.0.1:8001 php -S 127.0.0.1:8000 shop/index.php &
php -S 127.0.0.1:8001 bank/index.php &
open http://127.0.0.1:8000/checkout?order=2024000123   # add &choice=OK to the bank's URL to pay
```
