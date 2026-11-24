<?php

declare(strict_types=1);

// A fake SporoPay endpoint for this example: php -S 127.0.0.1:8001 bank/index.php
// It does what the bank (or tomaj/payment-simulator) does, without the library:
//   GET /payment/sporopay-des  checks sign1 over pu_predcislo;pu_cislo;pu_kbanky;suma;mena;vs;ss;url;param
//   and redirects to url with the payment fields, the payer's account, result, real and SIGN2.
// The customer's choice comes from ?choice=OK|NOK (the buttons of the real payment page).

$secret = getenv('SPOROPAY_SHARED_SECRET');
$secret = is_string($secret) && $secret !== '' ? $secret : 'Z3qY08EpvLlAAoMZdnyUdQ==';
$uri = $_SERVER['REQUEST_URI'] ?? '';

/**
 * SHA-1 of the fields joined with ';', padded with 0xFF to 24 bytes, DES-EDE3-CBC with a zero IV, base64.
 * A 16-byte key K1 K2 is used as K1 K2 K1.
 *
 * @param array<string, string> $fields
 */
function sporopaySign(array $fields, string $secret): string
{
    $key = (string) base64_decode($secret, true);
    $key = strlen($key) === 16 ? $key . substr($key, 0, 8) : $key;
    $hash = sha1(implode(';', $fields), true) . str_repeat("\xFF", 4);

    return base64_encode((string) openssl_encrypt($hash, 'DES-EDE3-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, str_repeat("\0", 8)));
}

if (!is_string($uri) || parse_url($uri, PHP_URL_PATH) !== '/payment/sporopay-des') {
    http_response_code(404);
    echo "Not found\n";
    return;
}

$field = static fn(string $name): string => is_string($_GET[$name] ?? null) ? $_GET[$name] : '';
$names = ['pu_predcislo', 'pu_cislo', 'pu_kbanky', 'suma', 'mena', 'vs', 'ss', 'url', 'param'];
$payment = array_combine($names, array_map($field, $names));

if (!hash_equals(sporopaySign($payment, $secret), $field('sign1'))) {
    http_response_code(400);
    echo "SporoPay: the payment request has an invalid signature\n";
    return;
}

$result = $field('choice') === 'OK' ? 'OK' : 'NOK';
$return = ['u_predcislo' => '000000', 'u_cislo' => '1234567890', 'u_kbanky' => '0900'] + $payment
    + ['result' => $result, 'real' => $result];
$return['SIGN2'] = sporopaySign($return, $secret);

header('Location: ' . $payment['url'] . '?' . http_build_query($return), true, 302);
