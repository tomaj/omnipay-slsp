<p align="center"><a href="https://tomaj.github.io/omnipay-slsp/"><img src="docs/public/logo.svg" width="96" height="96" alt="omnipay-slsp"></a></p>

# Omnipay SporoPay (SLSP)

**SporoPay (Slovenská sporiteľňa, Slovakia) gateway for the [Omnipay](https://omnipay.thephpleague.com/) payment processing library**

**Documentation: https://tomaj.github.io/omnipay-slsp/**

[Getting started](https://tomaj.github.io/omnipay-slsp/guide/getting-started) · [Payment flow](https://tomaj.github.io/omnipay-slsp/guide/payment-flow) · [Examples](https://tomaj.github.io/omnipay-slsp/examples/) · [API reference](https://tomaj.github.io/omnipay-slsp/api/reference) · [Changelog](https://tomaj.github.io/omnipay-slsp/changelog)

[![Latest Stable Version](https://img.shields.io/packagist/v/tomaj/omnipay-slsp.svg)](https://packagist.org/packages/tomaj/omnipay-slsp)
[![CI](https://github.com/tomaj/omnipay-slsp/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/tomaj/omnipay-slsp/actions/workflows/ci.yml?query=branch%3Amaster)
[![Coverage Status](https://coveralls.io/repos/github/tomaj/omnipay-slsp/badge.svg?branch=master)](https://coveralls.io/github/tomaj/omnipay-slsp?branch=master)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)](https://phpstan.org/)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/tomaj/omnipay-slsp/badge)](https://scorecard.dev/viewer/?uri=github.com/tomaj/omnipay-slsp)
[![Documentation](https://img.shields.io/badge/docs-4.x-4f46e5.svg)](https://tomaj.github.io/omnipay-slsp/)
[![License](https://img.shields.io/packagist/l/tomaj/omnipay-slsp.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/dependency-v/tomaj/omnipay-slsp/php.svg)](https://packagist.org/packages/tomaj/omnipay-slsp)

## What is omnipay-slsp?

An Omnipay gateway (`SporoPay`) for SporoPay, the internet banking payment button of Slovenská sporiteľňa. It:

- builds the signed (3DES) redirect that sends the customer to SporoPay (`purchase()`),
- verifies the signed return request of the bank and checks it belongs to the order you expect, including the amount (`completePurchase()`),
- can send payments to a [payment simulator](https://github.com/tomaj/payment-simulator) in test mode.

## Installation

The library requires PHP 8.4 or later with `ext-openssl`, and an HTTP client for Omnipay (any PSR-18 client, for example `php-http/guzzle7-adapter`).

```bash
composer require tomaj/omnipay-slsp php-http/guzzle7-adapter
```

## Usage

Redirect the customer to the bank:

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('SporoPay');
$gateway->setSharedSecret(getenv('SPOROPAY_SHARED_SECRET') ?: 'Z3qY08EpvLlAAoMZdnyUdQ=='); // base64 3DES key

$response = $gateway->purchase([
    'accountNumberPrefix' => '000000', // your account: prefix (pu_predcislo)
    'accountNumber' => '0013662162',   // and number (pu_cislo)
    'amount' => '10.00',
    'vs' => '2024000123',            // variable symbol, unique per order
    'ss' => '0000000001',
    'rurl' => 'https://shop.example/payment/return',
    'param' => 'order=2024000123',
])->send();

$response->redirect(); // HTTP redirect to SporoPay
```

On the return URL, verify the bank's answer against the order you expect:

```php
$response = $gateway->completePurchase([
    'vs' => $order->vs,
    'amount' => $order->amount,
])->send();

if ($response->isSuccessful()) {
    // paid
}
```

The [documentation](https://tomaj.github.io/omnipay-slsp/) covers every parameter, the signed strings, test mode, error handling and testing. The [examples](https://tomaj.github.io/omnipay-slsp/examples/) in [`examples/`](examples) run in CI, from the payment redirect to a shop and a fake bank on localhost.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md).

## Security

Report vulnerabilities privately, see [SECURITY.md](SECURITY.md).

## License

MIT, see [LICENSE.md](LICENSE.md).
