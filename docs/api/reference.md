# API reference

Every public class and method of the package. All classes are `final`; namespace `Omnipay\SporoPay`. Methods inherited from Omnipay are listed where they matter for this gateway; the rest behave as in [omnipay/common](https://github.com/thephpleague/omnipay-common).

## `Gateway`

`final class Gateway extends Omnipay\Common\AbstractGateway`

| Method | Returns | Description |
|---|---|---|
| `__construct(?ClientInterface $httpClient = null, ?Request $httpRequest = null)` | | Omnipay's constructor. `$httpRequest` (Symfony) is where `completePurchase()` reads the return; `null` means `Request::createFromGlobals()`. `$httpClient` `null` discovers a PSR-18 client. |
| `getName()` | `string` | `'SporoPay SLSP Gateway'` |
| `getShortName()` | `string` | `'SporoPay'`, the name for `Omnipay::create('SporoPay')` |
| `getDefaultParameters()` | `array<string, string>` | `['sharedSecret' => '', 'testHost' => '']` |
| `initialize(array $parameters = [])` | `$this` | Resets the parameters to the defaults, then sets the given ones (Omnipay) |
| `getParameters()` | `array` | All parameters (Omnipay) |
| `getSharedSecret()` / `setSharedSecret(?string $value)` | `?string` / `static` | Base64-encoded 3DES key |
| `getTestMode()` / `setTestMode($value)` | `?bool` / `static` | Redirect to the simulator (Omnipay) |
| `getTestHost()` / `setTestHost(?string $value)` | `?string` / `static` | Simulator host for test mode |
| `purchase(array $parameters = [])` | `Message\PurchaseRequest` | A new payment, with the gateway's parameters overridden by `$parameters` |
| `completePurchase(array $parameters = [])` | `Message\CompletePurchaseRequest` | Verifies the bank's return, with the gateway's parameters overridden by `$parameters` |
| `supportsPurchase()`, `supportsCompletePurchase()` | `bool` | `true` (Omnipay); the other `supports*()` return `false` |

Getters return `null` for a parameter that is not a string or an integer (an integer is returned as a string).

## `Message\PurchaseRequest`

`final class PurchaseRequest extends Omnipay\Common\Message\AbstractRequest`

| Member | Returns | Description |
|---|---|---|
| `const string CURRENCY = 'EUR'` | | Sent as `mena` |
| `const string BANK_CODE = '0900'` | | Sent as `pu_kbanky` |
| `getData()` | `array<string, ?string>` | `pu_predcislo`, `pu_cislo`, `pu_kbanky`, `suma`, `mena`, `vs`, `ss`, `url`, `param`. Throws `InvalidRequestException` when `pu_predcislo`, `pu_cislo`, `vs`, `ss`, `rurl` or `param` is `null`, or the amount is invalid. |
| `send()` | `PurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data)` | `PurchaseResponse` | Adds `sign1`. Throws `InvalidRequestException('Purchase data must be an array')`, and the exceptions of `Des3Sign::sign()`. |
| `generateSignature(string $data)` | `string` | The 3DES signature of `$data` with `sharedSecret` |
| `getEndpoint()` | `string` | `https://ib.slsp.sk/epayment/epayment/epayment.xml`; in test mode `<testHost or https://platby.tomaj.sk>/payment/sporopay-des` |
| `getSharedSecret()` / `setSharedSecret(?string)` | `?string` / `static` | 3DES key |
| `getTestHost()` / `setTestHost(?string)` | `?string` / `static` | Simulator host |
| `getAccountNumberPrefix()` / `setAccountNumberPrefix(?string)` | `?string` / `static` | `pu_predcislo` |
| `getAccountNumber()` / `setAccountNumber(?string)` | `?string` / `static` | `pu_cislo` |
| `getVs()` / `setVs(?string)` | `?string` / `static` | `vs`, variable symbol |
| `getSs()` / `setSs(?string)` | `?string` / `static` | `ss`, specific symbol |
| `getRurl()` / `setRurl(?string)` | `?string` / `static` | `url`, return URL |
| `getParam()` / `setParam(?string)` | `?string` / `static` | `param`, free text |
| `getAmount()` / `setAmount($value)` | `?string` / `static` | `suma`, formatted with two decimals (Omnipay) |
| `getTestMode()` / `setTestMode($value)` | `?bool` / `static` | (Omnipay) |

## `Message\PurchaseResponse`

`final class PurchaseResponse extends Omnipay\Common\Message\AbstractResponse implements RedirectResponseInterface`

| Method | Returns | Description |
|---|---|---|
| `isSuccessful()` | `bool` | Always `false` |
| `isRedirect()` | `bool` | Always `true` |
| `getRedirectUrl()` | `string` | The endpoint, `?` and `http_build_query(getRedirectData())`. Throws `Exception\LogicException('PurchaseResponse requires a PurchaseRequest')` when created for another request. |
| `getRedirectMethod()` | `string` | `'GET'` |
| `getRedirectData()` | `array` | The signed fields |
| `getRedirectResponse()` | `Symfony\Component\HttpFoundation\RedirectResponse` | A 302 to `getRedirectUrl()` (Omnipay) |
| `redirect()` | `void` | Sends `getRedirectResponse()` (Omnipay) |
| `getData()` | `mixed` | The same fields as `getRedirectData()` |
| `getRequest()` | `RequestInterface` | The `PurchaseRequest` |

## `Message\CompletePurchaseRequest`

`final class CompletePurchaseRequest extends Omnipay\Common\Message\AbstractRequest`

| Member | Returns | Description |
|---|---|---|
| `getData()` | `array{RES: string, VS: string, SS: string, AMOUNT: string, CURRENCY: string, REAL: string}` | Reads the return from the query string and runs [the checks](../guide/complete-purchase#the-checks). Throws `InvalidRequestException`, and the exceptions of `Des3Sign::sign()`. Missing or non-string query parameters are read as `''`. |
| `send()` | `CompletePurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data)` | `CompletePurchaseResponse` | Wraps `$data` |
| `getSharedSecret()` / `setSharedSecret(?string)` | `?string` / `static` | 3DES key |
| `getVs()` / `setVs(?string)` | `?string` / `static` | Expected variable symbol (required) |
| `getSs()` / `setSs(?string)` | `?string` / `static` | Expected specific symbol; `null` skips the check |
| `getAmount()` / `setAmount($value)` | `?string` / `static` | Expected amount (required), formatted with two decimals (Omnipay) |

## `Message\CompletePurchaseResponse`

`final class CompletePurchaseResponse extends Omnipay\Common\Message\AbstractResponse`

| Member | Returns | Description |
|---|---|---|
| `const string SUCCESS = 'OK'` | | The `result` of a paid return |
| `isSuccessful()` | `bool` | `getRes() === 'OK'` |
| `getRes()` | `?string` | `result`: `'OK'` or `'NOK'` |
| `getVs()` | `?string` | Returned (verified) variable symbol |
| `getSs()` | `?string` | Returned specific symbol (verified when `ss` was passed) |
| `getAmount()` | `?string` | Returned (verified) amount, `suma` |
| `getCurrency()` | `?string` | Returned (verified) currency, `mena`: `'EUR'` |
| `getReal()` | `?string` | The signed `real` field as sent by the bank |
| `getData()` | `mixed` | `['RES' => ..., 'VS' => ..., 'SS' => ..., 'AMOUNT' => ..., 'CURRENCY' => ..., 'REAL' => ...]` |
| `isRedirect()`, `isCancelled()` | `bool` | `false` (Omnipay) |
| `getMessage()`, `getCode()`, `getTransactionReference()` | `null` | (Omnipay) |

The getters return `null` when the data has no string under that key (only possible when you build the response yourself).

## `Sign\Des3Sign`

`final readonly class Des3Sign`

| Method | Returns | Description |
|---|---|---|
| `sign(string $input, string $secret)` | `string` | Base64 of DES-EDE3-CBC (zero IV, no padding) of SHA-1(`$input`) padded with `0xFF` to 24 bytes; the key is the base64-decoded `$secret` (16 bytes `K1 K2` used as `K1 K2 K1`). Throws `Exception\InvalidArgumentException` when `$secret` is not strict base64, `Exception\RuntimeException` when OpenSSL fails. |

## Exceptions

| Class | Extends / implements | Thrown when |
|---|---|---|
| `Exception\SporoPayException` | interface, extends `Omnipay\Common\Exception\OmnipayException`, `\Throwable` | implemented by every exception the package defines |
| `Exception\InvalidArgumentException` | `\InvalidArgumentException`, implements `SporoPayException` | the shared secret is not base64 |
| `Exception\RuntimeException` | `\RuntimeException`, implements `SporoPayException` | OpenSSL cannot compute the signature |
| `Exception\LogicException` | `\LogicException`, implements `SporoPayException` | `PurchaseResponse::getRedirectUrl()` without a `PurchaseRequest` |
| `Omnipay\Common\Exception\InvalidRequestException` | Omnipay, implements `OmnipayException` | missing parameters, invalid amount, invalid signature, field mismatch |

See [Error handling](../guide/errors) for every message.
