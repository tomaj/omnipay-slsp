<?php

declare(strict_types=1);

// Return URL: SporoPay redirects the customer back with every field of the payment, the payer's
// account, result, real and SIGN2 in the query string. This runs the handler against the returns a
// shop sees in practice. The bank's side is played by bankReturn(), which signs with openssl_encrypt()
// directly, independently of the library.

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\SporoPay\Gateway;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/vendor/autoload.php';

const SHARED_SECRET = 'Z3qY08EpvLlAAoMZdnyUdQ==';   // the payment simulator's key

/**
 * What SporoPay appends to the return URL. SIGN2 covers every field, in this order.
 *
 * @param array<string, string> $change fields that differ from a payment of order 2024000123
 * @return array<string, string>
 */
function bankReturn(string $result, array $change = []): array
{
    $query = array_replace([
        'u_predcislo' => '000000', 'u_cislo' => '1234567890', 'u_kbanky' => '0900',   // the payer's account
        'pu_predcislo' => '000000', 'pu_cislo' => '0013662162', 'pu_kbanky' => '0900', // yours
        'suma' => '10.00', 'mena' => 'EUR', 'vs' => '2024000123', 'ss' => '0000000001',
        'url' => 'https://shop.example/return', 'param' => 'order=2024000123',
        'result' => $result, 'real' => $result,
    ], $change);
    $key = base64_decode(SHARED_SECRET, true);
    $key .= substr($key, 0, 8);
    $hash = sha1(implode(';', $query), true) . str_repeat("\xFF", 4);
    $query['SIGN2'] = base64_encode((string) openssl_encrypt($hash, 'DES-EDE3-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, str_repeat("\0", 8)));

    return $query;
}

/**
 * The return URL controller. $order is the order the customer is paying (loaded from the session
 * or by the returned vs), with the amount and the symbols you sent in purchase().
 *
 * @param array{vs: string, ss: string, amount: string, status: string} $order
 */
function handleReturn(Request $request, array &$order): string
{
    // in a framework, pass the current request; plain PHP can use new Gateway() (createFromGlobals)
    $gateway = new Gateway(null, $request);
    $gateway->setSharedSecret(SHARED_SECRET);

    try {
        $response = $gateway->completePurchase([
            'vs' => $order['vs'],
            'amount' => $order['amount'],
            'ss' => $order['ss'],
        ])->send();
    } catch (InvalidRequestException $e) {
        // forged, tampered or another payment's return: never change the order, log it
        return '400 rejected: ' . $e->getMessage();
    }

    if (!$response->isSuccessful()) {
        // result=NOK: rejected or cancelled, offer to pay again
        return "200 not paid ({$response->getRes()}), order stays {$order['status']}";
    }
    if ($order['status'] === 'paid') {
        // reload or back button: the same return again, mark the order paid only once
        return '200 already paid';
    }
    $order['status'] = 'paid';

    // SIGN2 covers the amount and the currency, and completePurchase() compared them with the order
    return "200 paid {$response->getAmount()} {$response->getCurrency()}, vs {$response->getVs()}, real={$response->getReal()}";
}

$order = ['vs' => '2024000123', 'ss' => '0000000001', 'amount' => '10.00', 'status' => 'new'];

$scenarios = [
    'rejected by the bank' => bankReturn('NOK'),
    'result changed to OK' => ['result' => 'OK', 'real' => 'OK'] + bankReturn('NOK'),
    'return of a 1.00 payment' => bankReturn('OK', ['suma' => '1.00']),
    'return of another order' => bankReturn('OK', ['vs' => '2024000999']),
    'paid in another currency' => bankReturn('OK', ['mena' => 'CZK']),
    'another specific symbol' => bankReturn('OK', ['ss' => '0000000002']),
    'return URL opened directly' => [],
    'paid' => bankReturn('OK'),
    'paid, page reloaded' => bankReturn('OK'),
];

foreach ($scenarios as $name => $query) {
    $request = Request::create('https://shop.example/return', 'GET', $query);
    printf("%-28s %s\n", $name . ':', handleReturn($request, $order));
}
