<?php

declare(strict_types=1);

// Return URL: SporoPay redirects the customer here with the payment fields, result, real and SIGN2.

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\SporoPay\Gateway;

require __DIR__ . '/../vendor/autoload.php';

$gateway = new Gateway(); // reads the query through Request::createFromGlobals()
$gateway->setSharedSecret('Z3qY08EpvLlAAoMZdnyUdQ==');   // the same key as in purchase.php

// Your orders, normally a database table. Store what you sent to the bank.
$orders = [
    '2024000123' => ['amount' => '10.00', 'ss' => '0000000001', 'paid' => false],
];

$vs = $_GET['vs'] ?? null;
$order = is_string($vs) ? $orders[$vs] ?? null : null;
if (!is_string($vs) || $order === null) {
    echo "Unknown order\n";
    return;
}

try {
    // verifies SIGN2, then that vs, suma, mena (EUR) and ss are the ones of this order
    $response = $gateway->completePurchase([
        'vs' => $vs,
        'amount' => $order['amount'],
        'ss' => $order['ss'],
    ])->send();
} catch (InvalidRequestException $e) {
    // tampered or foreign return: never mark the order paid
    echo 'Invalid return: ' . $e->getMessage() . "\n";
    return;
}

if ($response->isSuccessful()) {
    // result=OK
    $order['paid'] = true;
    echo "Order {$vs} paid, {$response->getAmount()} {$response->getCurrency()}\n";
} else {
    // result=NOK: rejected or cancelled
    echo "Order {$vs} not paid: {$response->getRes()}\n";
}
