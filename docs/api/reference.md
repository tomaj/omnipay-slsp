# API reference

All classes are `final`. Namespace `Omnipay\SporoPay`.

## `Gateway`

`extends Omnipay\Common\AbstractGateway`

| Method | Description |
|---|---|
| `__construct(?ClientInterface $httpClient = null, ?Request $httpRequest = null)` | Omnipay's constructor. `$httpRequest` (Symfony) is where `completePurchase()` reads the return; defaults to `Request::createFromGlobals()`. |
| `getName(): string` | `'SporoPay SLSP Gateway'` |
| `getShortName(): string` | `'SporoPay'` (for `Omnipay::create('SporoPay')`) |
| `getDefaultParameters(): array` | `['sharedSecret' => '', 'testHost' => '']` |
| `getSharedSecret(): ?string` / `setSharedSecret(?string): static` | base64 3DES key |
| `getTestHost(): ?string` / `setTestHost(?string): static` | Simulator host for test mode |
| `getTestMode(): bool` / `setTestMode(bool): static` | Omnipay |
| `purchase(array $parameters = []): Message\PurchaseRequest` | New payment |
| `completePurchase(array $parameters = []): Message\CompletePurchaseRequest` | Verify the return |

## `Message\PurchaseRequest`

`extends Omnipay\Common\Message\AbstractRequest`

| Member | Description |
|---|---|
| `const string CURRENCY = 'EUR'` | `mena` |
| `const string BANK_CODE = '0900'` | `pu_kbanky` |
| `getData(): array<string, ?string>` | `pu_predcislo`, `pu_cislo`, `pu_kbanky`, `suma`, `mena`, `vs`, `ss`, `url`, `param`. Throws `InvalidRequestException` when a required parameter is missing. |
| `send(): PurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data): PurchaseResponse` | Adds `sign1`. Throws `InvalidRequestException` when `$data` is not an array. |
| `generateSignature(string $data): string` | 3DES signature of `$data` with `sharedSecret` |
| `getEndpoint(): string` | Production or test-mode URL, see [Test mode](../guide/test-mode) |
| `get/setSharedSecret`, `get/setTestHost`, `get/setAccountNumberPrefix`, `get/setAccountNumber`, `get/setVs`, `get/setSs`, `get/setRurl`, `get/setParam` | `?string` getters, setters take `?string` and return `static` |
| `getAmount(): ?string` / `setAmount()` | Omnipay |

## `Message\PurchaseResponse`

`extends Omnipay\Common\Message\AbstractResponse implements RedirectResponseInterface`

| Method | Description |
|---|---|
| `isSuccessful(): bool` | Always `false` |
| `isRedirect(): bool` | Always `true` |
| `getRedirectUrl(): string` | Endpoint with the signed query string. Throws `Exception\LogicException` when the response was created for another request type. |
| `getRedirectMethod(): string` | `'GET'` |
| `getRedirectData(): array` | The signed fields |
| `redirect(): void` | Omnipay: sends the redirect |

## `Message\CompletePurchaseRequest`

`extends Omnipay\Common\Message\AbstractRequest`

| Method | Description |
|---|---|
| `getData(): array{RES: string, VS: string, SS: string, AMOUNT: string, CURRENCY: string, REAL: string}` | Verifies the return, see [Operations](../guide/operations#completepurchase). Throws `InvalidRequestException`. |
| `send(): CompletePurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data): CompletePurchaseResponse` | Wraps the data |
| `get/setSharedSecret` | base64 3DES key |
| `get/setVs` | Expected variable symbol (required) |
| `getAmount()` / `setAmount()` | Expected amount (required) |
| `get/setSs` | Expected specific symbol (optional) |

Missing or non-string query parameters are read as `''`.

## `Message\CompletePurchaseResponse`

`extends Omnipay\Common\Message\AbstractResponse`

| Member | Description |
|---|---|
| `const string SUCCESS = 'OK'` | |
| `isSuccessful(): bool` | `result === 'OK'` |
| `getRes(): ?string` | `result` |
| `getReal(): ?string` | `real` |
| `getVs(): ?string`, `getSs(): ?string` | Returned symbols |
| `getAmount(): ?string`, `getCurrency(): ?string` | Returned `suma` and `mena` |

## `Sign\Des3Sign`

`final readonly class`

| Method | Description |
|---|---|
| `sign(string $input, string $secret): string` | base64 of DES-EDE3-CBC(SHA-1(`$input`) + `0xFF` padding), see [Signatures](../guide/signatures) |

## Exceptions

| Class | When |
|---|---|
| `Omnipay\Common\Exception\InvalidRequestException` | Missing parameters, invalid signature, return of another payment |
| `Exception\InvalidArgumentException` (`extends \InvalidArgumentException`) | Shared secret not base64 |
| `Exception\RuntimeException` (`extends \RuntimeException`) | OpenSSL failure |
| `Exception\LogicException` (`extends \LogicException`) | Programming errors |
| `Exception\SporoPayException` (interface, `extends OmnipayException`) | Implemented by every exception the package defines |

Catch `Omnipay\Common\Exception\OmnipayException` to handle every exception of the gateway.
