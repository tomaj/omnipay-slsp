# Configuration

Parameters are set on the gateway, where they become the defaults of every request, or passed in the array of `purchase([...])` / `completePurchase([...])`, which overrides the gateway values for that request. Every parameter also has a typed setter on the request. Omnipay maps array keys to setters, so `accountNumberPrefix` and `account_number_prefix` both call `setAccountNumberPrefix()`.

```php
use Omnipay\SporoPay\Gateway;

$gateway = new Gateway();
$gateway->setSharedSecret(getenv('SPOROPAY_SHARED_SECRET') ?: 'Z3qY08EpvLlAAoMZdnyUdQ==');
$gateway->setTestMode(false);

// the same with initialize(), for example from a configuration array
$gateway->initialize([
    'sharedSecret' => getenv('SPOROPAY_SHARED_SECRET') ?: 'Z3qY08EpvLlAAoMZdnyUdQ==',
    'testMode' => false,
]);

var_dump($gateway->getSharedSecret(), $gateway->getTestMode());
```

<!-- snippet: output -->
```text
string(24) "Z3qY08EpvLlAAoMZdnyUdQ=="
bool(false)
```

::: tip initialize() resets
Like in every Omnipay gateway, `initialize()` first resets all parameters to their defaults and then applies the array.
:::

## Gateway parameters

| Parameter | Setter / getter | Type | Default | Description |
|---|---|---|---|---|
| `sharedSecret` | `setSharedSecret()` / `getSharedSecret()` | `?string` | `''` | The base64-encoded 3DES key from Slovenská sporiteľňa, 16 or 24 bytes after decoding. Signs the request and verifies the return; never sent anywhere. Decoded strictly: whitespace, line breaks and missing `=` padding are accepted, any other character outside the base64 alphabet throws `Exception\InvalidArgumentException('Shared secret must be base64 encoded')` when signing. Required: an empty or whitespace-only secret (including the default `''`) throws `Shared secret must not be empty`, a key of another length than 16 or 24 bytes `Shared secret must be a 16- or 24-byte 3DES key`, in `purchase()` and `completePurchase()`. |
| `testMode` | `setTestMode()` / `getTestMode()` | `bool` | not set (off) | `true` redirects to the payment simulator instead of SporoPay. See [Test mode](./test-mode). |
| `testHost` | `setTestHost()` / `getTestHost()` | `?string` | `''` (means `https://platby.tomaj.sk`) | Scheme and host of the simulator used in test mode, without a trailing slash, for example `http://127.0.0.1:8080`. |

::: warning Check the secret at startup
The gateway's default `sharedSecret` is an empty string. Since 4.0 every `purchase()` and `completePurchase()` throws `Exception\InvalidArgumentException` with an empty secret or a key of the wrong length, instead of signing with a key padded with zero bytes. Check your configuration when the application starts as well, for example with `getenv('SPOROPAY_SHARED_SECRET') ?: throw new RuntimeException(...)`, so a broken deployment fails at once instead of at the first payment.
:::

## `purchase()` parameters

| Parameter | Setter | Sent as | Required | Description |
|---|---|---|---|---|
| `accountNumberPrefix` | `setAccountNumberPrefix()` | `pu_predcislo` | yes | Prefix of your account at Slovenská sporiteľňa (`000000` when it has none). |
| `accountNumber` | `setAccountNumber()` | `pu_cislo` | yes | Your account number. |
| | | `pu_kbanky` | | Always `0900`, the bank code of Slovenská sporiteľňa (`PurchaseRequest::BANK_CODE`). |
| `amount` | `setAmount()` | `suma` | yes | Amount in euro. Omnipay formats it with two decimals: `'10'`, `10` and `'10.0'` are sent as `10.00`; more than two decimals throws `InvalidRequestException('Amount precision is too high for currency.')`, a negative amount `'A negative amount is not allowed.'`. See the [Amount formats example](../examples/amount-formats). |
| | | `mena` | | Always `EUR` (`PurchaseRequest::CURRENCY`). |
| `vs` | `setVs()` | `vs` | yes | Variable symbol: identifies the order, up to 10 digits. Use a value unique per order. |
| `ss` | `setSs()` | `ss` | yes | Specific symbol, up to 10 digits. |
| `rurl` | `setRurl()` | `url` | yes | Return URL: where the bank sends the customer back with the result. |
| `param` | `setParam()` | `param` | yes | Free text returned to you unchanged and signed in both directions, for example `order=2024000123`. |
| `sharedSecret`, `testMode`, `testHost` | | | | Inherited from the gateway, can be overridden per request. |

Every field is part of `sign1`, see [Signatures](./signatures). When a required parameter is `null`, `purchase()` throws `InvalidRequestException` with the name of the **sent** field: `The pu_predcislo parameter is required`, `The pu_cislo parameter is required`, `The vs parameter is required`, `The ss parameter is required`, `The rurl parameter is required`, `The param parameter is required`, `The amount parameter is required`.

The currency is not a parameter: SporoPay pays in euro. Omnipay's generic request parameters (`currency`, `description`, `transactionId`, `returnUrl`, ...) are accepted but not sent to the bank; use `rurl` for the return URL.

## `completePurchase()` parameters

| Parameter | Type | Required | Description |
|---|---|---|---|
| `vs` | `?string` | yes | The variable symbol of the order you are completing. The returned `vs` must be exactly this value (`'0123'` is not `'123'`). |
| `amount` | `string\|int\|float` | yes | The amount of the order. Formatted like in `purchase()` (`10`, `'10.0'` and `'10.00'` all mean `'10.00'`) and compared with the returned `suma`. |
| `ss` | `?string` | no | When set, the returned `ss` must be exactly this value. `null` skips the check. |
| `sharedSecret` | `?string` | yes | Usually inherited from the gateway. |

The returned fields are read from the query string of the HTTP request the gateway was created with.

## The HTTP request in frameworks

Omnipay gives the gateway a Symfony `Request`, `Request::createFromGlobals()` by default, which is right for plain PHP. In a framework, or a long-running server (RoadRunner, FrankenPHP worker mode, Swoole), create the gateway per request with the current request:

```php
use Omnipay\SporoPay\Gateway;
use Symfony\Component\HttpFoundation\Request;

$request = Request::create('https://shop.example/return', 'GET', ['vs' => '2024000123', 'result' => 'OK', 'SIGN2' => '...']);

$gateway = new Gateway(null, $request);                  // null: Omnipay discovers the HTTP client
// or: Omnipay::create('SporoPay', null, $request);

echo $gateway->getShortName() . "\n";
```

<!-- snippet: output -->
```text
SporoPay
```

Laravel's `Illuminate\Http\Request` extends the Symfony request, so you can pass it directly; with a PSR-7 request, convert it with `symfony/psr-http-message-bridge`.
