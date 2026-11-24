<?php

declare(strict_types=1);

// SporoPay signs the amount (suma) in both directions, and completePurchase() compares the returned
// suma with the amount of your order as text. Omnipay formats both sides with two decimals, so the
// order may store the amount as '10', 10, '10.0' or 10.5; what matters is that it is the same amount.

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\SporoPay\Gateway;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/vendor/autoload.php';

const SHARED_SECRET = 'Z3qY08EpvLlAAoMZdnyUdQ==';

$gateway = new Gateway();
$gateway->setSharedSecret(SHARED_SECRET);

echo "purchase(): amount → suma\n";
foreach (['10', 10, '10.0', 10.5, '0.99', '1000000', '9.999', '-5'] as $amount) {
    try {
        $suma = $gateway->purchase(['amount' => $amount])->getAmount();
        $result = var_export($suma, true);
    } catch (InvalidRequestException $e) {
        $result = 'InvalidRequestException: ' . $e->getMessage();
    }
    printf("  %-12s %s\n", var_export($amount, true), $result);
}

/**
 * A genuine return of a payment of $suma for order 2024000123.
 *
 * @return array<string, string>
 */
function signedReturnOf(string $suma): array
{
    $query = [
        'u_predcislo' => '000000', 'u_cislo' => '1234567890', 'u_kbanky' => '0900',
        'pu_predcislo' => '000000', 'pu_cislo' => '0013662162', 'pu_kbanky' => '0900',
        'suma' => $suma, 'mena' => 'EUR', 'vs' => '2024000123', 'ss' => '0000000001',
        'url' => 'https://shop.example/return', 'param' => '', 'result' => 'OK', 'real' => 'OK',
    ];
    $key = base64_decode(SHARED_SECRET, true);
    $hash = sha1(implode(';', $query), true) . str_repeat("\xFF", 4);
    $query['SIGN2'] = base64_encode((string) openssl_encrypt($hash, 'DES-EDE3-CBC', $key . substr($key, 0, 8), OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, str_repeat("\0", 8)));

    return $query;
}

echo "\ncompletePurchase(): the order's amount against the returned suma\n";
$cases = [
    ['10', '10.00'],
    [10.0, '10.00'],
    ['10.5', '10.50'],
    ['10.50', '10.5'],    // the bank returns suma as it was sent; purchase() never sends '10.5'
    ['10.00', '1.00'],
    [null, '10.00'],
];
foreach ($cases as [$amount, $suma]) {
    $gateway = new Gateway(null, Request::create('https://shop.example/return', 'GET', signedReturnOf($suma)));
    $gateway->setSharedSecret(SHARED_SECRET);
    try {
        $response = $gateway->completePurchase(['vs' => '2024000123', 'amount' => $amount])->send();
        $result = 'paid ' . $response->getAmount();
    } catch (InvalidRequestException $e) {
        $result = 'rejected: ' . $e->getMessage();
    }
    printf("  amount %-8s suma %-6s %s\n", var_export($amount, true), $suma, $result);
}
