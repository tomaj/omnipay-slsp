# Configuration

Parameters are set on the gateway (they become the defaults of every request) or passed to `purchase([...])` / `completePurchase([...])`, which override the gateway values. Each parameter also has a typed setter. Omnipay maps array keys to setters, so `accountNumberPrefix` and `account_number_prefix` both call `setAccountNumberPrefix()`.

```php
use Omnipay\SporoPay\Gateway;

$gateway = new Gateway();
$gateway->setSharedSecret($_ENV['SPOROPAY_SHARED_SECRET']);
$gateway->setTestMode(false);
```

## Gateway parameters

| Parameter | Setter | Description |
|---|---|---|
| `sharedSecret` | `setSharedSecret()` | The base64-encoded 3DES key from Slovenská sporiteľňa (16 or 24 bytes after decoding). Decoded strictly: whitespace and missing `=` padding are accepted, other characters throw `Exception\InvalidArgumentException`. Never sent to the browser. |
| `testMode` | `setTestMode()` | `true` sends payments to the payment simulator instead of `https://ib.slsp.sk/epayment/epayment/epayment.xml`. Default `false`. See [Test mode](./test-mode). |
| `testHost` | `setTestHost()` | Scheme and host of the simulator used in test mode, without a trailing slash. Default `https://platby.tomaj.sk`. |

## `purchase()` parameters

| Parameter | Setter | Sent as | Description |
|---|---|---|---|
| `accountNumberPrefix` | `setAccountNumberPrefix()` | `pu_predcislo` | Prefix of your account at Slovenská sporiteľňa (`000000` when none). Required. |
| `accountNumber` | `setAccountNumber()` | `pu_cislo` | Your account number. Required. |
| | | `pu_kbanky` | Always `0900` (`PurchaseRequest::BANK_CODE`). |
| `amount` | `setAmount()` | `suma` | Amount in EUR, formatted by Omnipay with two decimals (`'10.00'`). |
| | | `mena` | Always `EUR` (`PurchaseRequest::CURRENCY`). |
| `vs` | `setVs()` | `vs` | Variable symbol: identifies the order, up to 10 digits. Required. Use a value unique per order. |
| `ss` | `setSs()` | `ss` | Specific symbol, up to 10 digits. Required. |
| `rurl` | `setRurl()` | `url` | Return URL the bank redirects the customer to. Required. |
| `param` | `setParam()` | `param` | Free text returned to you unchanged (for example `order=2024000123`). Required. |

All of them are part of `sign1`, see [Signatures](./signatures).

## `completePurchase()` parameters

| Parameter | Description |
|---|---|
| `vs` | The variable symbol of the order you are completing. **Required.** The return's `vs` must be exactly this value. |
| `amount` | The amount of the order. **Required.** Formatted like in `purchase()` (`10`, `'10.0'` and `'10.00'` all mean `'10.00'`) and compared with the return's `suma`. |
| `ss` | Optional. When set, the return's `ss` must be exactly this value. |
| `sharedSecret` | Usually inherited from the gateway. |

The return fields are read from the HTTP request that Omnipay gives the gateway: `Request::createFromGlobals()` by default. In a framework, pass the current Symfony request to the gateway:

```php
$gateway = new \Omnipay\SporoPay\Gateway(null, $symfonyRequest);
```
