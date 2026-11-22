<?php

declare(strict_types=1);

// Checkout: redirect the customer to SporoPay.
// Run: php -S 127.0.0.1:8000 -t examples, then open http://127.0.0.1:8000/purchase.php

use Omnipay\SporoPay\Gateway;

require __DIR__ . '/../vendor/autoload.php';

$gateway = new Gateway();
// the simulator's key; load the base64 3DES key from Slovenská sporiteľňa from the environment
$gateway->setSharedSecret('Z3qY08EpvLlAAoMZdnyUdQ==');
$gateway->setTestMode(true);                      // payment simulator instead of ib.slsp.sk

$response = $gateway->purchase([
    'accountNumberPrefix' => '000000',            // your account at Slovenská sporiteľňa (pu_predcislo)
    'accountNumber' => '0013662162',              // (pu_cislo)
    'amount' => '10.00',                          // EUR
    'vs' => '2024000123',                         // variable symbol: unique per order, up to 10 digits
    'ss' => '0000000001',                         // specific symbol, 10 digits
    'rurl' => 'https://shop.example/return.php',
    'param' => 'order=2024000123',                // returned to you unchanged
])->send();

// HTTP redirect (GET) to SporoPay with the signed parameters in the URL
$response->redirect();
