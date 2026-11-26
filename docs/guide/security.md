# Security

omnipay-slsp signs your payment requests and verifies the bank's returns. This page lists what that protects, what it does not, and what is left to your shop. To report a vulnerability, see the [security policy](https://github.com/tomaj/omnipay-slsp/security/policy): privately, not in a public issue.

## The shared secret

- The `sharedSecret` signs requests and verifies returns. Anyone who has it can forge an `OK` return. Keep it in the environment or a secret store; do not commit, log or send it to the browser.
- An empty secret, or one that does not decode to a 16- or 24-byte key, is rejected: `purchase()` and `completePurchase()` throw `Exception\InvalidArgumentException` instead of signing with a key that OpenSSL pads with zero bytes (an empty secret would be an all-zero key anyone can compute). Still check the configuration when the application starts, so a missing secret fails the deployment, not the first payment.
- The simulator's key (`Z3qY08EpvLlAAoMZdnyUdQ==`) is public. Use it only with test mode.

### Stack traces

Error reports (Tracy, Sentry, Monolog, `getTraceAsString()`) show the arguments of every call on the stack. The parameters that take the secret are marked with `#[\SensitiveParameter]`, so PHP shows `Object(SensitiveParameterValue)` instead of the key: `setSharedSecret()` on `Gateway`, `PurchaseRequest` and `CompletePurchaseRequest`, and `Des3Sign::sign()` (which throws for an empty or malformed key, with the key on the stack).

This does not cover arrays: a secret passed as `initialize(['sharedSecret' => ...])` or `purchase(['sharedSecret' => ...])` is still printed with the array. Set it with `setSharedSecret()`, and turn off arguments in traces in production with `zend.exception_ignore_args=On` in `php.ini` (the default of `php.ini-production`).

## What `completePurchase()` guarantees

When it returns without an exception:

- the bank (or whoever holds the shared secret) produced this return: `SIGN2` covers every field, and it was compared in constant time (`hash_equals()`);
- the return is for the variable symbol you passed, the amount you passed and the currency `EUR`, and for the specific symbol you passed, if any.

## What it cannot guarantee

- **Freshness.** The return has no timestamp or nonce; the same URL verifies again and again. Make "mark as paid" idempotent, and do not trigger side effects (shipping, e-mails) twice.
- **`param`, your account and the return URL.** They are signed, but `completePurchase()` does not compare them with anything. If you rely on `param`, compare it yourself.
- **That a return arrives at all.** The customer may close the browser after paying. Reconcile unpaid orders with the bank statement.

## Amount

Unlike some other Slovak bank buttons, SporoPay signs the amount in the return, and `completePurchase()` requires the amount of the order and compares it. Pass the amount stored with the order, never one read from the request.

## Test mode

Test mode only changes where the customer is redirected. Never use the simulator's public key in production, and keep `testMode` and the key in the same environment configuration.

## Transport

Serve the checkout and the return URL over HTTPS. The return carries no secret, but over plain HTTP anyone on the network could see and replay it.

## Reporting

Report vulnerabilities through [GitHub private vulnerability reporting](https://github.com/tomaj/omnipay-slsp/security/advisories/new). The [security policy](https://github.com/tomaj/omnipay-slsp/security/policy) lists the supported versions.
