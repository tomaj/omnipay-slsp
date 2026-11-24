---
layout: home
title: omnipay-slsp - SporoPay for Omnipay
titleTemplate: false
description: 'SporoPay (Slovenská sporiteľňa) gateway for Omnipay and PHP 8.4+: 3DES-signed payment redirects, returns verified against the order, amount and currency, test mode and five runnable examples.'

hero:
  name: omnipay-slsp
  text: SporoPay for Omnipay
  tagline: Send customers to Slovenská sporiteľňa internet banking with a signed redirect, and verify the bank's answer, including the amount, on your return URL. A gateway for the Omnipay payment library, PHP 8.4+.
  image:
    light: /illustrations/hero-light.svg
    dark: /illustrations/hero-dark.svg
    alt: A shop redirects the customer to SporoPay with a signed URL and gets a signed result back on its return URL
  actions:
    - theme: brand
      text: Get started
      link: /guide/getting-started
    - theme: alt
      text: Examples
      link: /examples/
    - theme: alt
      text: GitHub
      link: https://github.com/tomaj/omnipay-slsp

features:
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="4" width="30" height="40" rx="6"/><path class="ot-ink" d="M10 14h16M10 21h16M10 28h9"/><circle class="ot-acc" cx="36" cy="33" r="9"/><path class="ot-mark" d="M32 33h8M37 29.5l3.5 3.5-3.5 3.5"/></svg>'
    title: Signed redirect
    details: purchase() builds the 3DES-signed SporoPay URL that takes the customer to Slovenská sporiteľňa internet banking.
    link: /guide/purchase
    linkText: Starting a payment
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><path class="ot-soft" d="M24 3l17 6v13c0 11-7 19-17 23C14 41 7 33 7 22V9z"/><circle class="ot-acc" cx="24" cy="23" r="9"/><path class="ot-mark" d="M19.5 23.5l3.2 3 6-6.5"/></svg>'
    title: Verified returns
    details: completePurchase() checks SIGN2 in constant time, then that the variable symbol, the amount and the currency are the ones of your order.
    link: /guide/complete-purchase
    linkText: The return URL
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="8" width="42" height="32" rx="7"/><circle class="ot-acc" cx="16" cy="24" r="6"/><path class="ot-ink" d="M22 24h18M35 24v5M40 24v4"/></svg>'
    title: 3DES signatures
    details: The exact signed strings of sign1 and SIGN2, and an example that reproduces both with the openssl command line.
    link: /guide/signatures
    linkText: Signatures
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><path class="ot-soft" d="M17 4h14v12l11 20c2 4 0 8-5 8H11c-5 0-7-4-5-8l11-20z"/><path class="ot-acc" d="M12 31h24l3.5 6.5c1 2 0 3.5-2.5 3.5h-26c-2.5 0-3.5-1.5-2.5-3.5z"/><path class="ot-ink" d="M15 4h18M20 4v13M28 4v13"/></svg>'
    title: Test mode
    details: Point the gateway at the payment simulator or your own fake bank and test the whole flow without money.
    link: /guide/test-mode
    linkText: Test mode
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="6" width="42" height="30" rx="6"/><path class="ot-ink" d="M10 15l6 5-6 5M20 26h9M17 42h14M24 36v6"/><circle class="ot-acc" cx="37" cy="16" r="4"/></svg>'
    title: Tested examples
    details: Five runnable recipes, from the payment redirect to a shop and a fake bank on localhost. CI runs every one of them.
    link: /examples/
    linkText: Examples
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="3" width="19" height="19" rx="5"/><rect class="ot-soft" x="26" y="3" width="19" height="19" rx="5"/><rect class="ot-soft" x="3" y="26" width="19" height="19" rx="5"/><rect class="ot-acc" x="26" y="26" width="19" height="19" rx="5"/><path class="ot-mark" d="M31 35.5l3 3 6-6.5"/></svg>'
    title: Plain Omnipay
    details: The SporoPay gateway follows the Omnipay API, so it sits next to the other payment methods of your shop.
    link: /api/reference
    linkText: API reference
---

## What it looks like

The checkout sends the customer to the bank:

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('SporoPay');
$gateway->setSharedSecret(getenv('SPOROPAY_SHARED_SECRET') ?: 'Z3qY08EpvLlAAoMZdnyUdQ==');

$response = $gateway->purchase([
    'accountNumberPrefix' => '000000',          // your account at Slovenská sporiteľňa
    'accountNumber' => '0013662162',
    'amount' => '10.00',
    'vs' => '2024000123',                       // variable symbol: unique per order
    'ss' => '0000000001',                       // specific symbol
    'rurl' => 'https://shop.example/return',    // the bank sends the customer back here
    'param' => 'order=2024000123',              // returned unchanged
])->send();

$response->redirect(); // 302 to SporoPay with the signed query string
```

The return URL confirms the payment of the order you expect, for its amount:

```php
$response = $gateway->completePurchase(['vs' => $order->vs, 'amount' => $order->amount])->send();

if ($response->isSuccessful()) {
    echo "Order {$response->getVs()} is paid: {$response->getAmount()} {$response->getCurrency()}\n";
}
```

## Where to go next

<div class="ot-cards">
  <a href="./guide/getting-started.html"><strong>Getting started</strong><span>Install, configure, first payment</span></a>
  <a href="./guide/payment-flow.html"><strong>The payment flow</strong><span>What happens between your shop and SporoPay</span></a>
  <a href="./examples/"><strong>Examples</strong><span>Runnable recipes with their output</span></a>
  <a href="./guide/testing.html"><strong>Testing your integration</strong><span>Unit tests with signed returns, a fake bank, the simulator</span></a>
  <a href="./api/reference.html"><strong>API reference</strong><span>Every class, method and exception</span></a>
  <a href="./guide/security.html"><strong>Security</strong><span>What the signatures cover and what they do not</span></a>
</div>
