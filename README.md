Omnipay SLSP
=====================

[![CI](https://github.com/tomaj/omnipay-slsp/actions/workflows/ci.yml/badge.svg)](https://github.com/tomaj/omnipay-slsp/actions/workflows/ci.yml)

Slovenská sporiteľňa SporoPay driver for the [Omnipay](https://omnipay.thephpleague.com/) payment processing library.

Requirements
------------

- PHP 8.3 or newer with the `openssl` extension
- `omnipay/common` ^3.2 and a PSR-18 / HTTPlug client implementation (for example `php-http/guzzle7-adapter`)

Installation
------------

```bash
composer require tomaj/omnipay-slsp
```

Development
-----------

```bash
composer check   # code style, PHPStan, tests
composer cs-fix  # apply code style
```
