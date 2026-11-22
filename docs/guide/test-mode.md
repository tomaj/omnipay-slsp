# Test mode

With `testMode` on, `purchase()` redirects to a payment simulator instead of the bank:

| Mode | Endpoint |
|---|---|
| production (`testMode` false) | `https://ib.slsp.sk/epayment/epayment/epayment.xml` |
| test mode | `https://platby.tomaj.sk/payment/sporopay-des` |
| test mode with `testHost` | `<testHost>/payment/sporopay-des` |

```php
$gateway->setTestMode(true);
$gateway->setTestHost('http://localhost:8080'); // optional: your own simulator
```

## Payment simulator

[tomaj/payment-simulator](https://github.com/tomaj/payment-simulator) implements the SporoPay endpoint (and other Slovak bank buttons). It signs with the shared secret `Z3qY08EpvLlAAoMZdnyUdQ==`, shows whether your `sign1` matches, and offers links back to your return URL with a signed `OK` or `NOK` result (payer account `123456-9876543210/0900`). `https://platby.tomaj.sk` is a public instance; run your own with Docker for CI or private data.

Use the simulator's secret only in test mode, and never enable test mode in production: anyone could then produce a valid return with the public key.
