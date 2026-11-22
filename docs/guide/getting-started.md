# Getting started

omnipay-slsp is a gateway for [Omnipay](https://omnipay.thephpleague.com/) that pays through SporoPay, the internet banking payment button of Slovenská sporiteľňa (Slovakia). The customer is redirected to the bank, confirms the payment there and comes back to your return URL with the result.

## Requirements

- PHP 8.4 or newer (tested on 8.4, 8.5 and 8.6) with `ext-openssl`
- `omnipay/common` ^3.2
- an HTTP client for Omnipay: any PSR-18 client found by `php-http/discovery`, for example `php-http/guzzle7-adapter`. The gateway itself makes no HTTP calls, but Omnipay creates a client when the gateway is constructed.
- a SporoPay merchant contract with Slovenská sporiteľňa: your account (prefix and number) and the **shared secret** (a base64-encoded 3DES key)

## Installation

```bash
composer require tomaj/omnipay-slsp php-http/guzzle7-adapter
```

## The payment flow

1. Your checkout creates a `purchase()` request with your account, the amount, the order's variable and specific symbol, your return URL and a free `param`, and redirects the customer to SporoPay (`redirect()`, an HTTP GET redirect).
2. The customer pays (or cancels) in the bank.
3. The bank redirects the customer to your return URL with the payment fields, the payer's account, `result`, `real` and `SIGN2` in the query string.
4. Your return URL calls `completePurchase()` with the variable symbol and amount of the order. It verifies `SIGN2` and that the return belongs to that order, and `isSuccessful()` tells you whether the payment went through (`result=OK`).

## First payment

The checkout page:

<<< @/../examples/purchase.php

The return URL:

<<< @/../examples/return.php

Both files are in [`examples/`](https://github.com/tomaj/omnipay-slsp/tree/master/examples) and run in the test suite. With test mode on, the redirect goes to the [payment simulator](./test-mode), where you can choose a successful or failed payment.

## Next steps

- [Configuration](./configuration): every parameter
- [Operations](./operations): `purchase()` and `completePurchase()` in detail, error handling
- [Signatures](./signatures): the exact signed strings
- [Test mode](./test-mode): the simulator
