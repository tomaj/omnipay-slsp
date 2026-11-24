# The return URL

After the payment SporoPay redirects the customer to your `url` with every field in the query string:

```
https://shop.example/return?u_predcislo=000000&u_cislo=1234567890&u_kbanky=0900&pu_predcislo=000000&pu_cislo=0013662162&pu_kbanky=0900&suma=10.00&mena=EUR&vs=2024000123&ss=0000000001&url=https%3A%2F%2Fshop.example%2Freturn&param=order%3D2024000123&result=OK&real=OK&SIGN2=HC85nauH3W8MH7JcaWKvPBCTk%2BGuydGv
```

`completePurchase()` turns that into a verified answer for one order.

<<< @/../examples/return-url/run.php{php}

<<< @/../examples/return-url/expected-output.txt{text}

## The checks

`$gateway->completePurchase(['vs' => ..., 'amount' => ..., 'ss' => ...])->send()` runs these checks in this order. The first one that fails throws Omnipay's `InvalidRequestException` with the message in the table:

| # | Check | Message |
|---|---|---|
| 1 | `vs` was passed and is not empty | `The vs parameter is required` |
| 2 | `amount` was passed | `The amount parameter is required` |
| 3 | `SIGN2` equals the signature of `u_predcislo;u_cislo;u_kbanky;pu_predcislo;pu_cislo;pu_kbanky;suma;mena;vs;ss;url;param;result;real` (compared with `hash_equals()`) | `incorect signature` (sic) |
| 4 | the returned `vs` is exactly the expected `vs` | `Variable symbol mismatch` |
| 5 | the returned `suma` is exactly the expected `amount`, formatted with two decimals | `Amount mismatch` |
| 6 | the returned `mena` is `EUR` | `Currency mismatch` |
| 7 | when `ss` was passed: the returned `ss` is exactly that value | `Specific symbol mismatch` |

Computing the signature can also throw `Exception\InvalidArgumentException` (the secret is not base64) or `Exception\RuntimeException` (OpenSSL failed); both are configuration errors, not a bad return.

A missing query parameter (or one that is not a string, like `vs[]=1`) is read as `''`, so a request without `SIGN2` fails check 3. The signature check runs before the field checks: a return with a wrong signature always reports `incorect signature`.

## The response

When all checks pass, `send()` returns a `CompletePurchaseResponse`:

| Method | Returns |
|---|---|
| `isSuccessful()` | `true` when `result` is `OK` (`CompletePurchaseResponse::SUCCESS`) |
| `getRes()` | `'OK'` or `'NOK'` (the signed `result`) |
| `getVs()` | the returned, verified variable symbol |
| `getSs()` | the returned specific symbol (verified when you passed `ss`) |
| `getAmount()` | the returned, verified amount, for example `'10.00'` |
| `getCurrency()` | the returned, verified currency: `'EUR'` |
| `getReal()` | the signed `real` field as sent by the bank (`'OK'` or `'NOK'`) |
| `getData()` | `['RES' => ..., 'VS' => ..., 'SS' => ..., 'AMOUNT' => ..., 'CURRENCY' => ..., 'REAL' => ...]` |
| `isRedirect()`, `isCancelled()` | `false` (Omnipay defaults) |
| `getMessage()`, `getCode()`, `getTransactionReference()` | `null` (Omnipay defaults) |

The payer's account (`u_*`), your account (`pu_*`), `url` and `param` are signed but not returned by the response; read them from the request if you need them, after `send()` succeeded.

## Handling the result

- **Exception**: the request was not produced by the bank for this order and amount: forged, edited, or the genuine return of another payment. Do not change the order. Log it, and show the customer the order page with its real state.
- **`isSuccessful()`**: mark the order paid, once.
- **`NOK`**: the customer did not pay. Offer to pay again.

## Which order is it?

Load the order either from the customer's session or by the returned `vs` (`$request->query->get('vs')`), then pass **its** variable symbol and amount to `completePurchase()`:

```php
$vs = $_GET['vs'] ?? '';
$order = $orders[$vs] ?? null;          // your lookup; unknown vs: show a 404 page
$response = $gateway->completePurchase(['vs' => $order['vs'], 'amount' => $order['amount'], 'ss' => $order['ss']])->send();
echo $response->isSuccessful() ? "paid\n" : "not paid\n";
```

<!-- snippet: output -->
```text
paid
```

## Pass the right HTTP request

The query string is read from the Symfony request the gateway was created with, not from `$_GET`. Plain PHP works out of the box (`Request::createFromGlobals()`); in frameworks and long-running servers pass the current request: `new Gateway(null, $request)`, see [Configuration](./configuration#the-http-request-in-frameworks).
