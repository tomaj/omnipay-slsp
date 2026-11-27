---
title: Prechod z 3.x
---

# Prechod z 3.x na 4.0

Verzia 4.0 vyžaduje PHP 8.4, typuje verejné API, robí triedy `final` a kontroluje, že návrat z banky patrí k objednávke, ktorú dokončujete. Každá zmena nižšie ukazuje kód pred a po. Anglický originál je v [UPGRADE-4.0.md](https://github.com/tomaj/omnipay-slsp/blob/master/UPGRADE-4.0.md).

## `completePurchase()` vyžaduje očakávaný variabilný symbol a sumu {#completepurchase-requires-the-expected-variable-symbol-and-amount}

SporoPay podpisuje všetky polia návratu (`SIGN2`), verzia 3.x však kontrolovala len podpis, takže pravý úspešný návrat jednej objednávky (alebo nižšej sumy) dokončil aj inú. Verzia 4.0 vyžaduje variabilný symbol a sumu objednávky, ktorú dokončujete, a porovná ich s podpísanými `vs` a `suma` (a `mena` s `EUR`):

<!-- snippet: skip -->
```php
// 3.x
$response = $gateway->completePurchase()->send();
if ($response->isSuccessful()) {
    $order = $orders->findByVs($response->getVs());
    $order->markPaid();
}

// 4.0
$order = $orders->findByVs($request->getQuery('vs')); // or the order from your session
$response = $gateway->completePurchase([
    'vs' => $order->vs,
    'amount' => $order->amount,   // '10.00', 10.0 or '10' (compared as '10.00')
    'ss' => $order->ss,           // optional: also compare the specific symbol
])->send();
// throws InvalidRequestException('Variable symbol mismatch' / 'Amount mismatch' / ...) for another payment
if ($response->isSuccessful()) {
    $order->markPaid();
}
```

Bez `vs` alebo `amount` vyhodí `send()` výnimku `InvalidRequestException('The vs parameter is required')` / `('The amount parameter is required')`. Odpoveď vracia aj podpísané `getSs()`, `getAmount()`, `getCurrency()` a `getReal()` a `getData()` má kľúče `RES`, `VS`, `SS`, `AMOUNT`, `CURRENCY`, `REAL`.

## PHP 8.4 a omnipay/common 3.2 {#php-8-4-and-omnipay-common-3-2}

`composer.json` vyžaduje `"php": "^8.4"` a `"omnipay/common": "^3.2"`. Najprv aktualizujte PHP, potom:

```bash
composer require tomaj/omnipay-slsp:^4.0
```

## Typované prístupové metódy {#typed-accessors}

Gettery vracajú `?string`, settery berú `?string` a vracajú `static`. Volanie settera s `int` zo súboru s `declare(strict_types=1)` vyhodí `TypeError`; hodnoty odovzdané cez `purchase([...])` alebo `initialize()` sa stále konvertujú.

<!-- snippet: skip -->
```php
// 3.x
$request->setVs(2024000123);

// 4.0
$request->setVs('2024000123');
// or
$gateway->purchase(['vs' => 2024000123]);
```

## Zdieľaný kľúč v striktnom base64 {#strict-base64-shared-secret}

`Des3Sign::sign()` dekóduje zdieľaný kľúč striktným base64. Biele znaky, zalomenia riadkov a chýbajúce zarovnanie `=` sú v poriadku; iné znaky mimo abecedy base64 (URL-safe `-`/`_`, úvodzovky okolo hodnoty v konfiguračnom súbore) vyhodia `Omnipay\SporoPay\Exception\InvalidArgumentException`.

```php
// 3.x: the quotes were silently skipped
$gateway->setSharedSecret('"Z3qY08EpvLlAAoMZdnyUdQ=="');

// 4.0
$gateway->setSharedSecret('Z3qY08EpvLlAAoMZdnyUdQ==');
```

## Prázdny alebo krátky zdieľaný kľúč sa odmietne {#an-empty-or-short-shared-secret-is-rejected}

Verzia 3.x podpisovala akýmkoľvek nastaveným `sharedSecret`. OpenSSL doplní kľúč kratší ako 24 bajtov nulovými bajtmi, takže predvolené `''` brány (napríklad pri chýbajúcej premennej prostredia) podpisovalo platby a prijímalo návraty s 3DES kľúčom zo samých núl, ktorý vie vypočítať ktokoľvek. Verzia 4.0 vyhodí z `purchase()` aj `completePurchase()` `Omnipay\SporoPay\Exception\InvalidArgumentException`, keď je kľúč prázdny alebo len z bielych znakov (`Shared secret must not be empty`) alebo sa nedekóduje na 16- či 24-bajtový kľúč (`Shared secret must be a 16- or 24-byte 3DES key`). Kľúče od Slovenskej sporiteľne majú 16 alebo 24 bajtov, takže ich podpisy sa nemenia.

<!-- snippet: skip -->
```php
// 3.x: signed with an all-zero key when SPOROPAY_SHARED_SECRET was not set
$gateway->setSharedSecret((string) getenv('SPOROPAY_SHARED_SECRET'));

// 4.0: the gateway throws on the first payment; better fail when the application starts
$gateway->setSharedSecret(getenv('SPOROPAY_SHARED_SECRET') ?: throw new RuntimeException('SPOROPAY_SHARED_SECRET is not set'));
```

## Účet, platobné symboly, návratová URL a `param` sa kontrolujú {#account-payment-symbols-return-url-and-param-are-validated}

Verzia 3.x kontrolovala len to, či parametre platby nie sú `null`. `vs` s písmenami, číslo účtu s pomlčkou alebo relatívnu `rurl` podpísala a odoslala a Slovenská sporiteľňa ich odmietla až po presmerovaní zákazníka. Verzia 4.0 skontroluje formát v `purchase()` (`getData()`, `send()`) skôr, než sa čokoľvek podpíše, a vyhodí `InvalidRequestException`:

| Parameter | Prijme sa | Správa |
|---|---|---|
| `accountNumberPrefix` (`pu_predcislo`) | 1 až 6 číslic | `The pu_predcislo parameter must be up to 6 digits` |
| `accountNumber` (`pu_cislo`) | 1 až 10 číslic | `The pu_cislo parameter must be up to 10 digits` |
| `vs`, `ss` | 1 až 10 číslic | `The vs parameter must be up to 10 digits`, `The ss parameter must be up to 10 digits` |
| `rurl` | absolútna `https://` URL s hostiteľom, bez medzier, riadiacich znakov a `;`; v testovacom režime aj `http://` | `The rurl parameter must be an absolute https:// URL` (v testovacom režime `... https:// or http:// URL`) |
| `param` | ľubovoľný text bez riadiacich znakov a `;` (oddeľovača podpísaných polí) | `The param parameter must not contain control characters or ";"` |

<!-- snippet: skip -->
```php
// 3.x: signed and sent; SporoPay showed an error page after the redirect
$gateway->purchase([
    'accountNumberPrefix' => '', 'accountNumber' => '13662162/0900',
    'vs' => 'ORD-123', 'ss' => '1', 'amount' => '10.00', 'rurl' => '/payment/return', 'param' => "order=1\n",
])->send();

// 4.0: throws InvalidRequestException('The pu_predcislo parameter must be up to 6 digits') before signing
$gateway->purchase([
    'accountNumberPrefix' => '000000',                // 1 to 6 digits
    'accountNumber' => '0013662162',                  // 1 to 10 digits
    'vs' => '2024000123',                             // 1 to 10 digits
    'ss' => '0000000001',                             // 1 to 10 digits
    'amount' => '10.00',
    'rurl' => 'https://shop.example/payment/return',  // absolute https:// (http:// only in test mode)
    'param' => 'order=2024000123',                    // no control characters or ;
])->send();
```

Obyčajná `http://` návratová URL funguje len so zapnutým `testMode`, pre e-shop bežiaci lokálne. Podpisy platných platieb sa nemenia.

Oba podpisy spájajú polia znakom `;`, preto `completePurchase()` odmietne návrat, ktorý má `;` v niektorom podpísanom poli (`incorect signature`). Ak ste `;` používali v `param` alebo `rurl`, prejdite na iný oddeľovač (napríklad `&` alebo `,`) a platby začaté pred aktualizáciou dokončite ručne.

## Triedy `final` {#final-classes}

`Gateway`, `PurchaseRequest`, `PurchaseResponse`, `CompletePurchaseRequest`, `CompletePurchaseResponse` a `Des3Sign` sú `final` (`Des3Sign` je aj `readonly`). Kód, ktorý od nich dedil, ich musí namiesto toho obaliť.

<!-- snippet: skip -->
```php
// 3.x
class MyGateway extends \Omnipay\SporoPay\Gateway
{
    public function getName(): string
    {
        return 'My SporoPay';
    }
}

// 4.0: compose instead of extending
final class MyPayments
{
    public function __construct(private \Omnipay\SporoPay\Gateway $gateway) {}

    public function pay(string $vs, string $amount): \Omnipay\SporoPay\Message\PurchaseResponse
    {
        return $this->gateway->purchase(['vs' => $vs, 'amount' => $amount /* , ... */])->send();
    }
}
```

`Gateway::purchase()` a `Gateway::completePurchase()` deklarujú konkrétne návratové typy (`PurchaseRequest`, `CompletePurchaseRequest`) a `send()` vracia `PurchaseResponse` / `CompletePurchaseResponse`, takže kontroly cez `instanceof` už nie sú potrebné.

## Výnimky {#exceptions}

Každá výnimka, ktorú balík sám vyhadzuje, implementuje `Omnipay\SporoPay\Exception\SporoPayException` (rozširuje `OmnipayException` z Omnipay). Triedy dedia od SPL výnimiek, ktoré nahrádzajú, takže existujúce bloky `catch` fungujú ďalej:

| 3.x | 4.0 |
|---|---|
| `\InvalidArgumentException` (kľúč nie je base64) | `Omnipay\SporoPay\Exception\InvalidArgumentException` |
| `\RuntimeException` (zlyhanie OpenSSL) | `Omnipay\SporoPay\Exception\RuntimeException` |
| `\LogicException` (`PurchaseResponse` bez `PurchaseRequest`) | `Omnipay\SporoPay\Exception\LogicException` |

Neplatné požiadavky a návraty z banky stále vyhadzujú `InvalidRequestException` z Omnipay.

<!-- snippet: skip -->
```php
// 4.0: one catch for everything the gateway throws
try {
    $response = $gateway->completePurchase([...])->send();
} catch (\Omnipay\Common\Exception\OmnipayException $e) {
}
```

## Návrat sa číta z HTTP požiadavky, nie z `$_GET` {#the-return-request-is-read-from-the-http-request-not-get}

`CompletePurchaseRequest` číta polia návratu zo Symfony požiadavky, ktorú Omnipay odovzdá bráne (predvolene `Request::createFromGlobals()`), nie z `$_GET`. Čisté PHP funguje ďalej; vo frameworkoch a na dlho bežiacich serveroch odovzdajte aktuálnu požiadavku:

```php
use Omnipay\Omnipay;

// 3.x: worked only when $_GET was populated
$gateway = Omnipay::create('SporoPay');

// 4.0
$gateway = Omnipay::create('SporoPay', null, $symfonyRequest);
// or
$gateway = new \Omnipay\SporoPay\Gateway($httpClient, $symfonyRequest);
```
