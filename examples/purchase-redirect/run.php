<?php

declare(strict_types=1);

// Checkout: build the signed SporoPay request. SporoPay is a GET redirect: every field, with sign1,
// is in the query string of the URL the customer is sent to.

use Omnipay\SporoPay\Gateway;
use Omnipay\SporoPay\Message\PurchaseRequest;

require __DIR__ . '/vendor/autoload.php';

$gateway = new Gateway();
$gateway->setSharedSecret('Z3qY08EpvLlAAoMZdnyUdQ==');   // the payment simulator's key; load yours from the environment
$gateway->setTestMode(true);                            // the payment simulator instead of ib.slsp.sk

$request = $gateway->purchase([
    'accountNumberPrefix' => '000000',                  // your account at Slovenská sporiteľňa: prefix (pu_predcislo)
    'accountNumber' => '0013662162',                    // and number (pu_cislo)
    'amount' => '10.00',                                // EUR
    'vs' => '2024000123',                               // variable symbol: unique per order, up to 10 digits
    'ss' => '0000000001',                               // specific symbol, up to 10 digits
    'rurl' => 'https://shop.example/return',            // where the bank sends the customer back (url)
    'param' => 'order=2024000123',                      // returned to you unchanged
]);
$response = $request->send();

echo 'isRedirect:   ' . var_export($response->isRedirect(), true) . "\n";
echo 'isSuccessful: ' . var_export($response->isSuccessful(), true) . "\n";
echo 'method:       ' . $response->getRedirectMethod() . "\n";
echo 'endpoint:     ' . $request->getEndpoint() . "\n";
echo "fields:\n";
foreach ($response->getRedirectData() as $name => $value) {
    printf("  %-12s = %s\n", $name, var_export($value, true));
}
echo 'bank code ' . PurchaseRequest::BANK_CODE . ', currency ' . PurchaseRequest::CURRENCY . " (constants)\n";

echo "\nredirect URL:\n" . $response->getRedirectUrl() . "\n";

// In the checkout controller you would now redirect the browser: $response->redirect();
// getRedirectResponse() is the same redirect as a Symfony response, for frameworks.
$http = $response->getRedirectResponse();
echo "\nHTTP " . $http->getStatusCode() . ', Location is the redirect URL: ' . var_export($http->headers->get('Location') === $response->getRedirectUrl(), true) . "\n";
