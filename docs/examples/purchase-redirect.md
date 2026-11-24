# Payment redirect

The checkout side of a payment: build the signed request and look at the URL the customer is sent to.

**Shows:** `purchase()` parameters (`accountNumberPrefix`, `accountNumber`, `amount`, `vs`, `ss`, `rurl`, `param`), the fixed `pu_kbanky` and `mena`, the `PurchaseResponse` methods, `getRedirectResponse()`.

<<< @/../examples/purchase-redirect/run.php{php}

## Output

<<< @/../examples/purchase-redirect/expected-output.txt{text}

## Notes

- `sign1` is checked against the `openssl` command line in the [Signatures with openssl](./signatures) example.
- In a controller, call `$response->redirect()` and stop, or return `$response->getRedirectResponse()` from a framework action. See [Starting a payment](../guide/purchase).
