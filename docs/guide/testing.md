# Testing your integration

SporoPay has no server-to-server calls, so everything the bank does can be reproduced in a test: a payment request is a signed URL, a return is a signed query string. You need the shared secret of your test configuration and `openssl_encrypt()`.

## Unit test the return URL

Create the bank's return as a Symfony request and give it to the gateway. Sign it in the test with `openssl_encrypt()` directly, not with the library, so the test checks the library instead of repeating it:

```php
use Omnipay\SporoPay\Gateway;
use Symfony\Component\HttpFoundation\Request;

$secret = 'Z3qY08EpvLlAAoMZdnyUdQ==';
$bankReturn = static function (array $change = []) use ($secret): Request {
    $query = array_replace([
        'u_predcislo' => '000000', 'u_cislo' => '1234567890', 'u_kbanky' => '0900',
        'pu_predcislo' => '000000', 'pu_cislo' => '0013662162', 'pu_kbanky' => '0900',
        'suma' => '10.00', 'mena' => 'EUR', 'vs' => '2024000123', 'ss' => '0000000001',
        'url' => 'https://shop.example/return', 'param' => 'order=2024000123', 'result' => 'OK', 'real' => 'OK',
    ], $change);
    $key = base64_decode($secret, true);
    $hash = sha1(implode(';', $query), true) . str_repeat("\xFF", 4);
    $query['SIGN2'] = base64_encode(openssl_encrypt($hash, 'DES-EDE3-CBC', $key . substr($key, 0, 8), OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, str_repeat("\0", 8)));

    return Request::create('https://shop.example/return', 'GET', $query);
};

$gateway = new Gateway(null, $bankReturn(['result' => 'NOK', 'real' => 'NOK']));
$gateway->setSharedSecret($secret);
$response = $gateway->completePurchase(['vs' => '2024000123', 'amount' => '10.00'])->send();

echo var_export($response->isSuccessful(), true) . ' ' . $response->getRes() . "\n";
```

<!-- snippet: output -->
```text
false NOK
```

Cover at least: `OK`, `NOK`, an edited `result`, the return of a smaller amount, the return of another order, the return URL without parameters, and the same `OK` return twice. The [Return URL example](../examples/return-url) runs exactly these cases through one handler.

## Unit test the checkout

Assert on `getRedirectData()` (or parse `getRedirectUrl()`), and compute the expected `sign1` independently, once, with the `openssl` command line ([Signatures](./signatures#verify-with-openssl)). A test vector that the library computed itself would not catch a wrong field order.

```php
$data = $gateway->purchase([
    'accountNumberPrefix' => '000000', 'accountNumber' => '0013662162', 'amount' => '10.00',
    'vs' => '2024000123', 'ss' => '0000000001', 'rurl' => 'https://shop.example/return', 'param' => 'order=2024000123',
])->send()->getRedirectData();

// openssl, see Signatures
echo $data['sign1'] === 'U8BhKPlggZUOhAmr5OTr61Cj8p0cW9TF' ? "signature ok\n" : "signature changed\n";
```

<!-- snippet: output -->
```text
signature ok
```

## End to end without a bank

The [Full redirect flow example](../examples/full-flow) starts your shop and a small fake bank with `php -S`, and a script plays the browser: open the checkout, follow the redirect, follow the bank's redirect back. It runs in CI in a few seconds, offline. Copy `bank/index.php` into your own end-to-end tests and point `testHost` at it.

## Manual test with the simulator

Turn on [test mode](./test-mode) with the simulator's key and pay in the browser: `https://platby.tomaj.sk` (or your own instance) shows the request it received, whether `sign1` matches, and links back to your return URL with each result.

## Before going live

- [ ] the production shared secret comes from the environment, and test mode is off;
- [ ] the account prefix and number are your production account at Slovenská sporiteľňa;
- [ ] the return URL is reachable over HTTPS from the customer's browser (the bank only redirects, it does not call it);
- [ ] a real payment of a small amount reaches `isSuccessful()` and marks the order paid once;
- [ ] a rejected payment (`NOK`) leaves the order unpaid and lets the customer pay again;
- [ ] orders whose customer never came back are checked against the bank statement.
