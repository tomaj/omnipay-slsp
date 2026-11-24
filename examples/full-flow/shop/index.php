<?php

declare(strict_types=1);

// The shop: php -S 127.0.0.1:8000 shop/index.php
//   GET /checkout?order=<vs>  redirects the customer to SporoPay (302 with the signed query string)
//   GET /return?...&SIGN2=..  the return URL: verifies the bank's answer and updates the order
//   GET /orders               the order list
// BANK_URL is the SporoPay endpoint host (the fake bank, or a payment simulator), SHOP_DATA a writable directory.

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\SporoPay\Gateway;

require __DIR__ . '/../vendor/autoload.php';

$env = static function (string $name, string $default): string {
    $value = getenv($name);

    return is_string($value) && $value !== '' ? $value : $default;
};

$data = $env('SHOP_DATA', sys_get_temp_dir());
$file = $data . '/orders.json';
if (!is_file($file)) {
    // the shop's orders: the amount and the symbols sent to the bank are stored with each order
    file_put_contents($file, json_encode([
        '2024000123' => ['amount' => '10.00', 'status' => 'new'],
        '2024000124' => ['amount' => '24.90', 'status' => 'new'],
    ]));
}
/** @var array<string, array{amount: string, status: string}> $orders */
$orders = json_decode((string) file_get_contents($file), true);

$gateway = new Gateway();                  // reads the return from Request::createFromGlobals()
$gateway->setSharedSecret($env('SPOROPAY_SHARED_SECRET', 'Z3qY08EpvLlAAoMZdnyUdQ=='));
$gateway->setTestMode(true);
$gateway->setTestHost($env('BANK_URL', 'https://platby.tomaj.sk'));

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : null;
$vs = $_GET['order'] ?? $_GET['vs'] ?? null;
$order = is_string($vs) ? $orders[$vs] ?? null : null;

if ($path === '/checkout' && is_string($vs) && $order !== null) {
    $gateway->purchase([
        'accountNumberPrefix' => '000000',
        'accountNumber' => '0013662162',
        'amount' => $order['amount'],
        'vs' => $vs,
        'ss' => '0000000001',
        'rurl' => 'http://' . (is_string($host) ? $host : 'localhost') . '/return',
        'param' => 'order=' . $vs,
    ])->send()->redirect();
} elseif ($path === '/return' && is_string($vs) && $order !== null) {
    try {
        // SIGN2 covers every field: the amount and currency are verified too
        $response = $gateway->completePurchase(['vs' => $vs, 'amount' => $order['amount'], 'ss' => '0000000001'])->send();
    } catch (InvalidRequestException $e) {
        http_response_code(400);
        echo "Payment of order {$vs} could not be verified: {$e->getMessage()}\n";
        return;
    }
    if ($response->isSuccessful()) {
        $orders[$vs]['status'] = 'paid';
        echo "Thank you, order {$vs} is paid ({$response->getAmount()} {$response->getCurrency()}).\n";
    } else {
        echo "Order {$vs} was not paid ({$response->getRes()}). You can try again.\n";
    }
    file_put_contents($file, json_encode($orders));
} elseif ($path === '/orders') {
    echo implode(', ', array_map(static fn(string $id, array $row): string => "{$id} {$row['status']}", array_keys($orders), $orders)) . "\n";
} else {
    http_response_code(404);
    echo "Not found\n";
}
