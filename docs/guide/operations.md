# Operations

The gateway supports the two operations of SporoPay: starting a payment and completing it on the return URL. Refunds and payment status queries are not part of SporoPay's redirect interface.

## `purchase()`

Builds the signed redirect. Nothing is sent from your server: the customer's browser follows the redirect to the bank.

<<< @/../examples/purchase.php

`send()` returns a `PurchaseResponse`:

| Method | Value |
|---|---|
| `isSuccessful()` | `false`: the payment is not finished yet |
| `isRedirect()` | `true` |
| `getRedirectMethod()` | `'GET'` |
| `getRedirectUrl()` | `https://ib.slsp.sk/epayment/epayment/epayment.xml?pu_predcislo=…&sign1=…` (the simulator in test mode) |
| `getRedirectData()` | `pu_predcislo`, `pu_cislo`, `pu_kbanky`, `suma`, `mena`, `vs`, `ss`, `url`, `param`, `sign1` |
| `redirect()` | sends an HTTP redirect to `getRedirectUrl()` (Omnipay) |

In a framework, return your own redirect response to `$response->getRedirectUrl()` instead of calling `redirect()`.

`getData()` throws Omnipay's `InvalidRequestException` (`The pu_predcislo parameter is required`, ...) when the account prefix, account number, `vs`, `ss`, `rurl` or `param` is missing.

## `completePurchase()`

Verifies the return request of the bank. Call it on your return URL with the variable symbol and amount (and optionally the specific symbol) of the order:

<<< @/../examples/return.php

`send()` checks, in this order, and throws Omnipay's `InvalidRequestException` when a check fails:

| Check | Message |
|---|---|
| `vs` was passed and is not empty | `The vs parameter is required` |
| `amount` was passed | `The amount parameter is required` |
| `SIGN2` matches the 14 signed fields (constant-time comparison) | `incorect signature` |
| the returned `vs` is exactly the expected `vs` | `Variable symbol mismatch` |
| the returned `suma` is exactly the expected amount (`'10.00'`) | `Amount mismatch` |
| the returned `mena` is `EUR` | `Currency mismatch` |
| the returned `ss` is exactly the expected `ss`, when `ss` is passed | `Specific symbol mismatch` |

It returns a `CompletePurchaseResponse`:

| Method | Value |
|---|---|
| `isSuccessful()` | `true` when `result` is `OK` |
| `getRes()` | `result`: `OK` or `NOK` |
| `getReal()` | `real`, the second status field of the bank (`OK` or `NOK`), as signed |
| `getVs()`, `getSs()` | the returned (and verified) symbols |
| `getAmount()`, `getCurrency()` | the returned (and verified) `suma` and `mena` |
| `getData()` | `['RES', 'VS', 'SS', 'AMOUNT', 'CURRENCY', 'REAL']` |

`isSuccessful()` looks at `result` only, as in 3.x. `real` is exposed so you can apply the rules of your SporoPay contract to it.

### Handling the result

- **Exception**: the request was not produced by the bank for this order (forged, tampered, or the return of another order or amount). Do not change the order; log the attempt.
- **`isSuccessful()`**: mark the order paid.
- **`NOK`**: the customer did not pay. Let them try again.

The customer may open the return URL more than once (reload, back button). Make marking the order paid idempotent.

### Which order?

Load the order either by the returned `vs` (as in the example) or from the customer's session, and always pass its variable symbol and amount to `completePurchase()`.
