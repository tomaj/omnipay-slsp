# Upgrading from 3.x to 4.0

4.0 requires PHP 8.4, types the public API, makes the classes `final` and checks that the bank's return belongs to the order you are completing. Each change below shows the code before and after.

## `completePurchase()` requires the expected variable symbol and amount

SporoPay signs every field of the return (`SIGN2`), but 3.x only checked the signature, so a genuine success return of one order (or of a smaller amount) also completed another. 4.0 requires the variable symbol and amount of the order you are completing and compares them with the signed `vs` and `suma` (and `mena` with `EUR`):

<!-- snippet: skip -->
```php
// 3.x
$response = $gateway->completePurchase()->send();
if ($response->isSuccessful()) {
    $order = $orders->findByVs($response->getVs());
    $order->markPaid();
}

// 4.0
$order = $orders->findByVs($request->getQuery('vs')); // or the order from your session
$response = $gateway->completePurchase([
    'vs' => $order->vs,
    'amount' => $order->amount,   // '10.00', 10.0 or '10' (compared as '10.00')
    'ss' => $order->ss,           // optional: also compare the specific symbol
])->send();
// throws InvalidRequestException('Variable symbol mismatch' / 'Amount mismatch' / ...) for another payment
if ($response->isSuccessful()) {
    $order->markPaid();
}
```

Without `vs` or `amount`, `send()` throws `InvalidRequestException('The vs parameter is required')` / `('The amount parameter is required')`. The response also returns the signed `getSs()`, `getAmount()`, `getCurrency()` and `getReal()`, and `getData()` has the keys `RES`, `VS`, `SS`, `AMOUNT`, `CURRENCY`, `REAL`.

## PHP 8.4 and omnipay/common 3.2

`composer.json` requires `"php": "^8.4"` and `"omnipay/common": "^3.2"`. Upgrade PHP first, then:

```bash
composer require tomaj/omnipay-slsp:^4.0
```

## Typed accessors

Getters return `?string`, setters take `?string` and return `static`. Calling a setter with an `int` from a file with `declare(strict_types=1)` throws a `TypeError`; values passed through `purchase([...])` or `initialize()` are still converted.

<!-- snippet: skip -->
```php
// 3.x
$request->setVs(2024000123);

// 4.0
$request->setVs('2024000123');
// or
$gateway->purchase(['vs' => 2024000123]);
```

## Strict base64 shared secret

`Des3Sign::sign()` decodes the shared secret with strict base64. Whitespace, line breaks and missing `=` padding are accepted; other characters outside the base64 alphabet (URL-safe `-`/`_`, quotes around the value in a config file) throw `Omnipay\SporoPay\Exception\InvalidArgumentException`.

```php
// 3.x: the quotes were silently skipped
$gateway->setSharedSecret('"Z3qY08EpvLlAAoMZdnyUdQ=="');

// 4.0
$gateway->setSharedSecret('Z3qY08EpvLlAAoMZdnyUdQ==');
```

## An empty or short shared secret is rejected

3.x signed with whatever `sharedSecret` was set. OpenSSL pads a key shorter than 24 bytes with zero bytes, so the gateway's default `''` (a missing environment variable, for example) signed payments and accepted returns with an all-zero 3DES key that anyone can compute. 4.0 throws `Omnipay\SporoPay\Exception\InvalidArgumentException` from `purchase()` and `completePurchase()` when the secret is empty or only whitespace (`Shared secret must not be empty`) or does not decode to a 16- or 24-byte key (`Shared secret must be a 16- or 24-byte 3DES key`). Keys from Slovenská sporiteľňa have 16 or 24 bytes, so their signatures are unchanged.

<!-- snippet: skip -->
```php
// 3.x: signed with an all-zero key when SPOROPAY_SHARED_SECRET was not set
$gateway->setSharedSecret((string) getenv('SPOROPAY_SHARED_SECRET'));

// 4.0: the gateway throws on the first payment; better fail when the application starts
$gateway->setSharedSecret(getenv('SPOROPAY_SHARED_SECRET') ?: throw new RuntimeException('SPOROPAY_SHARED_SECRET is not set'));
```

## Account, payment symbols, return URL and `param` are validated

3.x checked only that the purchase parameters were not `null`. A `vs` with letters, an account number with a dash or a relative `rurl` was signed and sent, and Slovenská sporiteľňa rejected it only after the customer was redirected. 4.0 checks the format in `purchase()` (`getData()`, `send()`) before anything is signed and throws `InvalidRequestException`:

| Parameter | Accepted | Message |
|---|---|---|
| `accountNumberPrefix` (`pu_predcislo`) | 1 to 6 digits | `The pu_predcislo parameter must be up to 6 digits` |
| `accountNumber` (`pu_cislo`) | 1 to 10 digits | `The pu_cislo parameter must be up to 10 digits` |
| `vs`, `ss` | 1 to 10 digits | `The vs parameter must be up to 10 digits`, `The ss parameter must be up to 10 digits` |
| `rurl` | absolute `https://` URL with a host, no whitespace or control characters; `http://` too in test mode | `The rurl parameter must be an absolute https:// URL` (`... https:// or http:// URL` in test mode) |
| `param` | any text without control characters | `The param parameter must not contain control characters` |

<!-- snippet: skip -->
```php
// 3.x: signed and sent; SporoPay showed an error page after the redirect
$gateway->purchase([
    'accountNumberPrefix' => '', 'accountNumber' => '13662162/0900',
    'vs' => 'ORD-123', 'ss' => '1', 'amount' => '10.00', 'rurl' => '/payment/return', 'param' => "order=1\n",
])->send();

// 4.0: throws InvalidRequestException('The pu_predcislo parameter must be up to 6 digits') before signing
$gateway->purchase([
    'accountNumberPrefix' => '000000',                // 1 to 6 digits
    'accountNumber' => '0013662162',                  // 1 to 10 digits
    'vs' => '2024000123',                             // 1 to 10 digits
    'ss' => '0000000001',                             // 1 to 10 digits
    'amount' => '10.00',
    'rurl' => 'https://shop.example/payment/return',  // absolute https:// (http:// only in test mode)
    'param' => 'order=2024000123',                    // no control characters
])->send();
```

A plain `http://` return URL works only with `testMode` on, for a shop running locally. Signatures of valid payments are unchanged.

## Final classes

`Gateway`, `PurchaseRequest`, `PurchaseResponse`, `CompletePurchaseRequest`, `CompletePurchaseResponse` and `Des3Sign` are `final` (`Des3Sign` is also `readonly`). Code that extended them must wrap them instead.

<!-- snippet: skip -->
```php
// 3.x
class MyGateway extends \Omnipay\SporoPay\Gateway
{
    public function getName(): string
    {
        return 'My SporoPay';
    }
}

// 4.0: compose instead of extending
final class MyPayments
{
    public function __construct(private \Omnipay\SporoPay\Gateway $gateway) {}

    public function pay(string $vs, string $amount): \Omnipay\SporoPay\Message\PurchaseResponse
    {
        return $this->gateway->purchase(['vs' => $vs, 'amount' => $amount /* , ... */])->send();
    }
}
```

`Gateway::purchase()` and `Gateway::completePurchase()` declare their concrete return types (`PurchaseRequest`, `CompletePurchaseRequest`), and `send()` returns `PurchaseResponse` / `CompletePurchaseResponse`, so `instanceof` checks are no longer needed.

## Exceptions

Every exception the package throws itself implements `Omnipay\SporoPay\Exception\SporoPayException` (which extends Omnipay's `OmnipayException`). The classes extend the SPL exceptions they replace, so existing `catch` blocks keep working:

| 3.x | 4.0 |
|---|---|
| `\InvalidArgumentException` (secret not base64) | `Omnipay\SporoPay\Exception\InvalidArgumentException` |
| `\RuntimeException` (OpenSSL failure) | `Omnipay\SporoPay\Exception\RuntimeException` |
| `\LogicException` (`PurchaseResponse` without `PurchaseRequest`) | `Omnipay\SporoPay\Exception\LogicException` |

Invalid requests and bank returns still throw Omnipay's `InvalidRequestException`.

<!-- snippet: skip -->
```php
// 4.0: one catch for everything the gateway throws
try {
    $response = $gateway->completePurchase([...])->send();
} catch (\Omnipay\Common\Exception\OmnipayException $e) {
}
```

## The return request is read from the HTTP request, not `$_GET`

`CompletePurchaseRequest` reads the return fields from the Symfony request Omnipay passes to the gateway (`Request::createFromGlobals()` by default), not from `$_GET`. Plain PHP keeps working; in frameworks and long-running servers, pass the current request:

```php
use Omnipay\Omnipay;

// 3.x: worked only when $_GET was populated
$gateway = Omnipay::create('SporoPay');

// 4.0
$gateway = Omnipay::create('SporoPay', null, $symfonyRequest);
// or
$gateway = new \Omnipay\SporoPay\Gateway($httpClient, $symfonyRequest);
```
