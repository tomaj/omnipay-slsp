# Error handling

The gateway reports problems with exceptions; a payment that did not go through is not an exception but a response with `isSuccessful() === false`.

## What can be thrown

| Exception | Thrown by | When |
|---|---|---|
| `Omnipay\Common\Exception\InvalidRequestException` | `purchase()->send()`, `getData()` | a required parameter is `null` (`The pu_predcislo parameter is required`, `... pu_cislo ...`, `... vs ...`, `... ss ...`, `... rurl ...`, `... param ...`, `... amount ...`), an invalid amount (`Amount precision is too high for currency.`, `A negative amount is not allowed.`), `sendData()` called with something else than an array (`Purchase data must be an array`) |
| `Omnipay\Common\Exception\InvalidRequestException` | `completePurchase()->send()`, `getData()` | `The vs parameter is required`, `The amount parameter is required`, `incorect signature`, `Variable symbol mismatch`, `Amount mismatch`, `Currency mismatch`, `Specific symbol mismatch` ([the checks](./complete-purchase#the-checks)) |
| `Omnipay\SporoPay\Exception\InvalidArgumentException` | `Des3Sign::sign()`, so both `send()` | `Shared secret must be base64 encoded` |
| `Omnipay\SporoPay\Exception\RuntimeException` | `Des3Sign::sign()` | `Unable to compute the DES-EDE3-CBC signature: ...` (OpenSSL failed) |
| `Omnipay\SporoPay\Exception\LogicException` | `PurchaseResponse::getRedirectUrl()` | the response was created for another request than a `PurchaseRequest` (a programming error) |
| `Omnipay\Common\Exception\RuntimeException` | Omnipay, `initialize()` and setters | the request was changed after `send()` (`Request cannot be modified after it has been sent!`) |

Every exception the package defines implements `Omnipay\SporoPay\Exception\SporoPayException`, which extends Omnipay's `OmnipayException`; the classes also extend the SPL exception of the same name. `InvalidRequestException` and Omnipay's `RuntimeException` implement `OmnipayException` too, so one `catch` handles everything the gateway throws:

```php
use Omnipay\Common\Exception\OmnipayException;

try {
    $response = $gateway->completePurchase(['vs' => '2024000123', 'amount' => '100.00'])->send();
} catch (OmnipayException $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}
```

<!-- snippet: output -->
```text
Omnipay\Common\Exception\InvalidRequestException: Amount mismatch
```

## On the return URL

Every `InvalidRequestException` from `completePurchase()` means the same thing for the order: **this request does not prove a payment of this order and amount**. Do not change the order, answer with an error page (400 is fine), and log the message together with the query string; it helps to tell a misconfiguration from an attack:

| Message | Usual cause |
|---|---|
| `incorect signature` on every return | wrong `sharedSecret` (the simulator key in production or the other way round), or a router that changes or decodes the query string twice |
| `incorect signature` now and then | somebody edits the URL, or opens the return URL without parameters |
| `Variable symbol mismatch` | the return of another order, or the order loaded from the session differs from the one the customer paid in another tab |
| `Amount mismatch` | the return of another (cheaper) payment, or the order's amount changed after the redirect |
| `Currency mismatch` | a signed return with another currency than `EUR`; should not happen with SporoPay |
| `Specific symbol mismatch` | the `ss` passed to `completePurchase()` differs from the one sent in `purchase()` |
| `The vs parameter is required`, `The amount parameter is required` | a bug in your return URL: no order loaded |

`Exception\InvalidArgumentException` or `Exception\RuntimeException` on the return URL is a configuration problem (a malformed secret, a broken OpenSSL), not a bad return: alert yourself, not the customer.

## In the checkout

Exceptions from `purchase()` are configuration or programming errors: a missing return URL, amount or symbol, an amount with three decimals, a malformed secret. Let them reach your error handler, or validate the order before you call the gateway.
