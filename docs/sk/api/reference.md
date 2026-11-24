# Referencia API

Všetky verejné triedy a metódy balíka. Všetky triedy sú `final`; menný priestor `Omnipay\SporoPay`. Metódy zdedené z Omnipay sú uvedené tam, kde sú pre túto bránu dôležité; ostatné sa správajú ako v [omnipay/common](https://github.com/thephpleague/omnipay-common).

## `Gateway` {#gateway}

`final class Gateway extends Omnipay\Common\AbstractGateway`

| Metóda | Vráti | Popis |
|---|---|---|
| `__construct(?ClientInterface $httpClient = null, ?Request $httpRequest = null)` | | Konštruktor z Omnipay. `$httpRequest` (Symfony) je požiadavka, z ktorej `completePurchase()` číta návrat; `null` znamená `Request::createFromGlobals()`. Pri `$httpClient` rovnom `null` sa vyhľadá PSR-18 klient. |
| `getName()` | `string` | `'SporoPay SLSP Gateway'` |
| `getShortName()` | `string` | `'SporoPay'`, názov pre `Omnipay::create('SporoPay')` |
| `getDefaultParameters()` | `array<string, string>` | `['sharedSecret' => '', 'testHost' => '']` |
| `initialize(array $parameters = [])` | `$this` | Vráti parametre na predvolené hodnoty a potom nastaví zadané (Omnipay) |
| `getParameters()` | `array` | Všetky parametre (Omnipay) |
| `getSharedSecret()` / `setSharedSecret(?string $value)` | `?string` / `static` | Zdieľaný 3DES kľúč zakódovaný v base64 |
| `getTestMode()` / `setTestMode($value)` | `?bool` / `static` | Presmerovať na simulátor (Omnipay) |
| `getTestHost()` / `setTestHost(?string $value)` | `?string` / `static` | Host simulátora pre testovací režim |
| `purchase(array $parameters = [])` | `Message\PurchaseRequest` | Nová platba s parametrami brány prepísanými hodnotami z `$parameters` |
| `completePurchase(array $parameters = [])` | `Message\CompletePurchaseRequest` | Overí návrat z banky, s parametrami brány prepísanými hodnotami z `$parameters` |
| `supportsPurchase()`, `supportsCompletePurchase()` | `bool` | `true` (Omnipay); ostatné `supports*()` vracajú `false` |

Gettery vracajú `null` pre parameter, ktorý nie je reťazec ani celé číslo (celé číslo sa vráti ako reťazec).

## `Message\PurchaseRequest` {#message-purchaserequest}

`final class PurchaseRequest extends Omnipay\Common\Message\AbstractRequest`

| Člen | Vráti | Popis |
|---|---|---|
| `const string CURRENCY = 'EUR'` | | Posiela sa ako `mena` |
| `const string BANK_CODE = '0900'` | | Posiela sa ako `pu_kbanky` |
| `getData()` | `array<string, ?string>` | `pu_predcislo`, `pu_cislo`, `pu_kbanky`, `suma`, `mena`, `vs`, `ss`, `url`, `param`. Vyhodí `InvalidRequestException`, keď je `pu_predcislo`, `pu_cislo`, `vs`, `ss`, `rurl`, `param` alebo `amount` `null`, alebo je suma neplatná. |
| `send()` | `PurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data)` | `PurchaseResponse` | Pridá `sign1`. Vyhodí `InvalidRequestException('Purchase data must be an array')` a výnimky z `Des3Sign::sign()`. |
| `generateSignature(string $data)` | `string` | 3DES podpis `$data` so zdieľaným kľúčom (`sharedSecret`) |
| `getEndpoint()` | `string` | `https://ib.slsp.sk/epayment/epayment/epayment.xml`; v testovacom režime `<testHost alebo https://platby.tomaj.sk>/payment/sporopay-des` |
| `getSharedSecret()` / `setSharedSecret(?string)` | `?string` / `static` | 3DES kľúč |
| `getTestHost()` / `setTestHost(?string)` | `?string` / `static` | Host simulátora |
| `getAccountNumberPrefix()` / `setAccountNumberPrefix(?string)` | `?string` / `static` | `pu_predcislo` |
| `getAccountNumber()` / `setAccountNumber(?string)` | `?string` / `static` | `pu_cislo` |
| `getVs()` / `setVs(?string)` | `?string` / `static` | `vs`, variabilný symbol |
| `getSs()` / `setSs(?string)` | `?string` / `static` | `ss`, špecifický symbol |
| `getRurl()` / `setRurl(?string)` | `?string` / `static` | `url`, návratová URL |
| `getParam()` / `setParam(?string)` | `?string` / `static` | `param`, voľný text |
| `getAmount()` / `setAmount($value)` | `?string` / `static` | `suma`, naformátovaná na dve desatinné miesta (Omnipay) |
| `getTestMode()` / `setTestMode($value)` | `?bool` / `static` | (Omnipay) |

## `Message\PurchaseResponse` {#message-purchaseresponse}

`final class PurchaseResponse extends Omnipay\Common\Message\AbstractResponse implements RedirectResponseInterface`

| Metóda | Vráti | Popis |
|---|---|---|
| `isSuccessful()` | `bool` | Vždy `false` |
| `isRedirect()` | `bool` | Vždy `true` |
| `getRedirectUrl()` | `string` | Endpoint, `?` a `http_build_query(getRedirectData())`. Vyhodí `Exception\LogicException('PurchaseResponse requires a PurchaseRequest')`, keď bola odpoveď vytvorená pre inú požiadavku. |
| `getRedirectMethod()` | `string` | `'GET'` |
| `getRedirectData()` | `array` | Podpísané polia |
| `getRedirectResponse()` | `Symfony\Component\HttpFoundation\RedirectResponse` | Presmerovanie 302 na `getRedirectUrl()` (Omnipay) |
| `redirect()` | `void` | Pošle `getRedirectResponse()` (Omnipay) |
| `getData()` | `mixed` | Tie isté polia ako `getRedirectData()` |
| `getRequest()` | `RequestInterface` | `PurchaseRequest` |

## `Message\CompletePurchaseRequest` {#message-completepurchaserequest}

`final class CompletePurchaseRequest extends Omnipay\Common\Message\AbstractRequest`

| Člen | Vráti | Popis |
|---|---|---|
| `getData()` | `array{RES: string, VS: string, SS: string, AMOUNT: string, CURRENCY: string, REAL: string}` | Prečíta návrat z query stringu a spustí [kontroly](../guide/complete-purchase#the-checks). Vyhodí `InvalidRequestException` a výnimky z `Des3Sign::sign()`. Chýbajúce parametre alebo parametre, ktoré nie sú reťazce, sa čítajú ako `''`. |
| `send()` | `CompletePurchaseResponse` | `sendData(getData())` |
| `sendData(mixed $data)` | `CompletePurchaseResponse` | Obalí `$data` |
| `getSharedSecret()` / `setSharedSecret(?string)` | `?string` / `static` | 3DES kľúč |
| `getVs()` / `setVs(?string)` | `?string` / `static` | Očakávaný variabilný symbol (povinný) |
| `getSs()` / `setSs(?string)` | `?string` / `static` | Očakávaný špecifický symbol; `null` kontrolu preskočí |
| `getAmount()` / `setAmount($value)` | `?string` / `static` | Očakávaná suma (povinná), naformátovaná na dve desatinné miesta (Omnipay) |

## `Message\CompletePurchaseResponse` {#message-completepurchaseresponse}

`final class CompletePurchaseResponse extends Omnipay\Common\Message\AbstractResponse`

| Člen | Vráti | Popis |
|---|---|---|
| `const string SUCCESS = 'OK'` | | `result` zaplateného návratu |
| `isSuccessful()` | `bool` | `getRes() === 'OK'` |
| `getRes()` | `?string` | `result`: `'OK'` alebo `'NOK'` |
| `getVs()` | `?string` | Vrátený (overený) variabilný symbol |
| `getSs()` | `?string` | Vrátený špecifický symbol (overený, ak bol odovzdaný `ss`) |
| `getAmount()` | `?string` | Vrátená (overená) suma, `suma` |
| `getCurrency()` | `?string` | Vrátená (overená) mena, `mena`: `'EUR'` |
| `getReal()` | `?string` | Podpísané pole `real` tak, ako ho poslala banka |
| `getData()` | `mixed` | `['RES' => ..., 'VS' => ..., 'SS' => ..., 'AMOUNT' => ..., 'CURRENCY' => ..., 'REAL' => ...]` |
| `isRedirect()`, `isCancelled()` | `bool` | `false` (Omnipay) |
| `getMessage()`, `getCode()`, `getTransactionReference()` | `null` | (Omnipay) |

Gettery vrátia `null`, keď dáta pod daným kľúčom nemajú reťazec (to sa stane, len ak si odpoveď zostavíte sami).

## `Sign\Des3Sign` {#sign-des3sign}

`final readonly class Des3Sign`

| Metóda | Vráti | Popis |
|---|---|---|
| `sign(string $input, string $secret)` | `string` | Base64 z DES-EDE3-CBC (nulový IV, bez zarovnania) zo SHA-1(`$input`) doplneného hodnotou `0xFF` na 24 bajtov; kľúčom je `$secret` dekódovaný z base64 (16 bajtov `K1 K2` použitých ako `K1 K2 K1`, alebo 24 bajtov). Vyhodí `Exception\InvalidArgumentException`, keď je `$secret` prázdny alebo len z bielych znakov, nie je striktné base64 alebo nemá 16 či 24 bajtov; `Exception\RuntimeException`, keď OpenSSL zlyhá. |

## Výnimky {#exceptions}

| Trieda | Rozširuje / implementuje | Vyhodí sa, keď |
|---|---|---|
| `Exception\SporoPayException` | rozhranie, rozširuje `Omnipay\Common\Exception\OmnipayException`, `\Throwable` | implementuje ho každá výnimka, ktorú balík definuje |
| `Exception\InvalidArgumentException` | `\InvalidArgumentException`, implementuje `SporoPayException` | zdieľaný kľúč nie je base64 |
| `Exception\RuntimeException` | `\RuntimeException`, implementuje `SporoPayException` | OpenSSL nedokáže vypočítať podpis |
| `Exception\LogicException` | `\LogicException`, implementuje `SporoPayException` | `PurchaseResponse::getRedirectUrl()` bez `PurchaseRequest` |
| `Omnipay\Common\Exception\InvalidRequestException` | Omnipay, implementuje `OmnipayException` | chýbajúce parametre, neplatná suma, neplatný podpis, nezhoda polí |

Všetky správy nájdete v [Spracovaní chýb](../guide/errors).
