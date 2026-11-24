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
