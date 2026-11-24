# Getting started

omnipay-slsp is a gateway for [Omnipay](https://omnipay.thephpleague.com/), the PHP payment processing library. It pays through SporoPay, the internet banking payment button of Slovenská sporiteľňa (Slovakia): the customer is redirected to the bank, confirms the payment there and comes back to your return URL with a signed result.

## Requirements

- PHP 8.4 or newer (tested on 8.4, 8.5 and 8.6) with `ext-openssl`
- [`omnipay/common`](https://packagist.org/packages/omnipay/common) ^3.2 (installed with the package)
- an HTTP client for Omnipay: any PSR-18 client that `php-http/discovery` finds, for example `php-http/guzzle7-adapter`. The gateway itself makes no HTTP calls, but Omnipay creates a client when the gateway is constructed.
- a SporoPay contract with Slovenská sporiteľňa: your **account** (prefix and number) and the **shared secret**, a base64-encoded 3DES key. Until you have them, use [test mode](./test-mode).

## Installation

```bash
composer require tomaj/omnipay-slsp php-http/guzzle7-adapter
```

## Create the gateway

Create it with Omnipay's factory (`SporoPay` is the gateway's short name) or with `new`:

```php
use Omnipay\Omnipay;
use Omnipay\SporoPay\Gateway;

$gateway = Omnipay::create('SporoPay');   // or: new Gateway()
$gateway->setSharedSecret(getenv('SPOROPAY_SHARED_SECRET') ?: 'Z3qY08EpvLlAAoMZdnyUdQ==');
$gateway->setTestMode(true);               // the payment simulator until you go live

echo $gateway->getName() . "\n";
```

<!-- snippet: output -->
```text
SporoPay SLSP Gateway
```

Keep the shared secret in the environment or a secret store, never in the code. Every parameter is described in [Configuration](./configuration).

## 1. Send the customer to the bank

The checkout creates a `purchase()` request with your account, the amount, the order's symbols, your return URL and a free-text `param`. `redirect()` answers with a 302 to SporoPay; every field and the signature `sign1` are in the query string:

```php
$response = $gateway->purchase([
    'accountNumberPrefix' => '000000',
    'accountNumber' => '0013662162',
    'amount' => '10.00',
    'vs' => '2024000123',
    'ss' => '0000000001',
    'rurl' => 'https://shop.example/return',
    'param' => 'order=2024000123',
])->send();

echo $response->getRedirectMethod() . "\n";
echo 'sign1=' . $response->getRedirectData()['sign1'] . "\n";
// in the checkout controller: $response->redirect();
```

<!-- snippet: output -->
```text
GET
sign1=U8BhKPlggZUOhAmr5OTr61Cj8p0cW9TF
```

Store the order with its amount and symbols before you redirect: the return URL needs them. See [Starting a payment](./purchase).

## 2. Verify the return

SporoPay sends the customer to your return URL with every field of the payment, the payer's account, `result`, `real` and `SIGN2` in the query string. Call `completePurchase()` with the variable symbol and the amount of the order you expect:

```php
use Omnipay\Common\Exception\InvalidRequestException;

try {
    $response = $gateway->completePurchase(['vs' => '2024000123', 'amount' => '10.00', 'ss' => '0000000001'])->send();
} catch (InvalidRequestException $e) {
    // forged or tampered return, or the return of another payment: do not touch the order
    exit('Invalid return: ' . $e->getMessage());
}

if ($response->isSuccessful()) {
    echo "Paid {$response->getAmount()} {$response->getCurrency()}, vs {$response->getVs()}\n";
} else {
    echo "Not paid: {$response->getRes()}\n";   // NOK: offer to pay again
}
```

<!-- snippet: output -->
```text
Paid 10.00 EUR, vs 2024000123
```

SporoPay signs every field of the return, so `completePurchase()` also checks the amount and the currency against your order. See [The return URL](./complete-purchase).

## Next steps

- [The payment flow](./payment-flow): what happens between the shop, the browser and the bank
- [Configuration](./configuration): every parameter
- [Examples](../examples/): runnable recipes, including a shop and a fake bank on localhost
- [Test mode](./test-mode): the payment simulator
