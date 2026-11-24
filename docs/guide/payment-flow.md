# The payment flow

SporoPay is a redirect payment: your server never talks to the bank. The customer's browser carries a signed request to Slovenská sporiteľňa and a signed answer back. The gateway builds the first and verifies the second.

<figure class="ot-diagram">
  <img class="light-only" src="/diagrams/flow-light.svg" alt="Sequence: the customer pays in the shop; purchase() answers with a redirect to SporoPay carrying the signed fields in the query string; the customer pays in internet banking; SporoPay redirects the browser to the return URL with every field and SIGN2; completePurchase() verifies the signature, the variable symbol, the amount and the currency" width="760" height="512">
  <img class="dark-only" src="/diagrams/flow-dark.svg" alt="Sequence: the customer pays in the shop; purchase() answers with a redirect to SporoPay carrying the signed fields in the query string; the customer pays in internet banking; SporoPay redirects the browser to the return URL with every field and SIGN2; completePurchase() verifies the signature, the variable symbol, the amount and the currency" width="760" height="512">
</figure>

## Step by step

1. **Checkout.** Your shop stores the order (amount, variable symbol, specific symbol) and calls `purchase()`. The gateway signs `pu_predcislo;pu_cislo;pu_kbanky;suma;mena;vs;ss;url;param` with the shared secret ([Signatures](./signatures)) into `sign1`.
2. **Redirect.** `redirect()` answers with a 302 to `https://ib.slsp.sk/epayment/epayment/epayment.xml` (or the simulator in [test mode](./test-mode)), the fields and `sign1` in the query string. Nothing goes from your server to the bank.
3. **Payment.** SporoPay checks `sign1`, the customer signs in to internet banking and confirms or rejects the payment.
4. **Return.** SporoPay redirects the browser to your `url` with the payer's account (`u_predcislo`, `u_cislo`, `u_kbanky`), your account and the payment fields as sent, `result` (`OK` or `NOK`), `real` and `SIGN2`, a signature over all of them.
5. **Verification.** Your return URL calls `completePurchase()` with the variable symbol and the amount (and the specific symbol) of the order. It recomputes `SIGN2`, compares it in constant time and checks the variable symbol, the amount, the currency and the specific symbol against the order. Then `isSuccessful()` tells you whether `result` is `OK`.

## What the return tells you

Every field of the return is covered by `SIGN2`:

| Field | Meaning |
|---|---|
| `u_predcislo`, `u_cislo`, `u_kbanky` | the payer's account: prefix, number, bank code |
| `pu_predcislo`, `pu_cislo`, `pu_kbanky` | your account, as sent |
| `suma`, `mena` | the amount and the currency (`EUR`), as sent |
| `vs`, `ss` | the variable and specific symbol, as sent |
| `url`, `param` | your return URL and your free text, as sent |
| `result` | `OK` paid, `NOK` not paid |
| `real` | another status field of the bank (`OK` or `NOK`), returned by `getReal()`; `isSuccessful()` uses only `result` |

There is no timestamp: the same return verifies again when it is replayed.

## Things that happen in practice

- **The customer closes the browser** after paying. There is no server-to-server notification in SporoPay, so the order stays unpaid in your shop until you check the bank statement.
- **The customer reloads the return page.** The same signed return arrives again: make marking the order paid idempotent ([example](../examples/return-url)).
- **The customer rejects the payment** in internet banking: `result=NOK`. Let them try again.
- **Somebody edits the query string**, for example `suma` or `result`. The signature no longer matches and `completePurchase()` throws ([Error handling](./errors)).
- **Somebody replays the genuine return of a cheaper payment.** The signature matches, but the variable symbol or the amount does not: `completePurchase()` throws.

The [full redirect flow example](../examples/full-flow) runs all of this on localhost with a fake bank.
