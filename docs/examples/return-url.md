# Return URL

The return URL controller, run against the returns a shop sees in practice. `bankReturn()` plays the bank and signs with `openssl_encrypt()`; `handleReturn()` is the code you would put in your controller.

**Shows:** `completePurchase()` with the expected `vs`, `amount` and `ss`, passing the current Symfony request to the gateway, every check and what to answer, idempotent "mark as paid".

<<< @/../examples/return-url/run.php{php}

## Output

<<< @/../examples/return-url/expected-output.txt{text}

## What each case tests

| Case | Why it ends like that |
|---|---|
| rejected by the bank | genuine return with `result=NOK`: `isSuccessful()` is `false` |
| result changed to OK | `result` and `real` are signed: the signature no longer matches |
| return of a 1.00 payment | a genuine `OK` return of the same order but for `1.00`: `Amount mismatch` |
| return of another order | a genuine `OK` return for `vs=2024000999`: `Variable symbol mismatch` |
| paid in another currency | a genuinely signed `mena=CZK`: `Currency mismatch` |
| another specific symbol | a genuine return with `ss=0000000002`, while the order expects `0000000001` |
| return URL opened directly | no `SIGN2` at all |
| paid | genuine `OK` return for this order and amount |
| paid, page reloaded | the same return again: the handler marks the order paid only once |
