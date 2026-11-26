# Test mode

With `testMode` on, `purchase()` redirects to a payment simulator instead of SporoPay, and accepts a plain `http://` return URL (`rurl`), for a shop running on `localhost`; production requires `https://`. Nothing else changes: the signatures, the return and `completePurchase()` work the same.

| Mode | `PurchaseRequest::getEndpoint()` |
|---|---|
| production (`testMode` not set or `false`) | `https://ib.slsp.sk/epayment/epayment/epayment.xml` |
| test mode | `https://platby.tomaj.sk/payment/sporopay-des` |
| test mode with `testHost` | `<testHost>/payment/sporopay-des` |

```php
$gateway->setTestMode(true);
echo $gateway->purchase()->getEndpoint() . "\n";

$gateway->setTestHost('http://127.0.0.1:8080');   // your own simulator
echo $gateway->purchase()->getEndpoint() . "\n";

$gateway->setTestMode(false);
echo $gateway->purchase()->getEndpoint() . "\n";
```

<!-- snippet: output -->
```text
https://platby.tomaj.sk/payment/sporopay-des
http://127.0.0.1:8080/payment/sporopay-des
https://ib.slsp.sk/epayment/epayment/epayment.xml
```

`testHost` is used only in test mode; an empty `testHost` means `https://platby.tomaj.sk`.

## Payment simulator

[tomaj/payment-simulator](https://github.com/tomaj/payment-simulator) implements the SporoPay endpoint (and the other Slovak bank buttons). It signs with the shared secret `Z3qY08EpvLlAAoMZdnyUdQ==`, shows whether your `sign1` matches, and offers links back to your return URL with a signed `OK` or `NOK` result. `https://platby.tomaj.sk` is a public instance; run your own with Docker for CI or private data and point `testHost` at it.

## A fake bank on localhost

For automated tests you do not need the simulator: SporoPay is two signed redirects, which a few lines of PHP can play. The [Full redirect flow example](../examples/full-flow) runs a shop and a fake bank with `php -S` and drives them like a browser; [Testing your integration](./testing) shows unit tests without any server.

## Never in production

The simulator's key is public: a shop that verifies returns with it accepts an `OK` return that anyone can sign. Use the simulator key only together with test mode, and load the key and the mode from the environment, so a production deploy cannot pick them up by accident.
