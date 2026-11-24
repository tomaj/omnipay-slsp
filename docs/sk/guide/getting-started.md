# Začíname

omnipay-slsp je brána pre [Omnipay](https://omnipay.thephpleague.com/), PHP knižnicu na spracovanie platieb. Platí cez SporoPay, platobné tlačidlo internet bankingu Slovenskej sporiteľne: zákazník je presmerovaný do banky, tam platbu potvrdí a vráti sa na vašu návratovú URL s podpísaným výsledkom.

## Požiadavky {#requirements}

- PHP 8.4 alebo novšie (testované na 8.4, 8.5 a 8.6) s rozšírením `ext-openssl`
- [`omnipay/common`](https://packagist.org/packages/omnipay/common) ^3.2 (nainštaluje sa s balíkom)
- HTTP klient pre Omnipay: ľubovoľný PSR-18 klient, ktorého nájde `php-http/discovery`, napríklad `php-http/guzzle7-adapter`. Samotná brána žiadne HTTP volania nerobí, Omnipay však klienta vytvára už pri vytvorení brány.
- zmluva so Slovenskou sporiteľňou na SporoPay: váš **účet** (predčíslie a číslo) a **zdieľaný kľúč** (`sharedSecret`), 3DES kľúč zakódovaný v base64. Kým ich nemáte, použite [testovací režim](./test-mode).

## Inštalácia {#installation}

```bash
composer require tomaj/omnipay-slsp php-http/guzzle7-adapter
```

## Vytvorenie brány {#create-the-gateway}

Vytvorte ju cez továreň Omnipay (`SporoPay` je krátky názov brány) alebo cez `new`:

```php
use Omnipay\Omnipay;
use Omnipay\SporoPay\Gateway;

$gateway = Omnipay::create('SporoPay');   // or: new Gateway()
$gateway->setSharedSecret(getenv('SPOROPAY_SHARED_SECRET') ?: 'Z3qY08EpvLlAAoMZdnyUdQ==');
$gateway->setTestMode(true);               // the payment simulator until you go live

echo $gateway->getName() . "\n";
```

<!-- snippet: output -->
```text
SporoPay SLSP Gateway
```

Zdieľaný kľúč držte v premenných prostredia alebo v úložisku tajomstiev, nikdy nie v kóde. Všetky parametre popisuje stránka [Konfigurácia](./configuration).

## 1. Pošlite zákazníka do banky {#_1-send-the-customer-to-the-bank}

Pokladňa vytvorí požiadavku `purchase()` s vaším účtom, sumou, symbolmi objednávky, návratovou URL a voľným textom `param`. `redirect()` odpovie presmerovaním 302 do SporoPay; všetky polia aj podpis `sign1` sú v query stringu:

```php
$response = $gateway->purchase([
    'accountNumberPrefix' => '000000',
    'accountNumber' => '0013662162',
    'amount' => '10.00',
    'vs' => '2024000123',
    'ss' => '0000000001',
    'rurl' => 'https://shop.example/return',
    'param' => 'order=2024000123',
])->send();

echo $response->getRedirectMethod() . "\n";
echo 'sign1=' . $response->getRedirectData()['sign1'] . "\n";
// in the checkout controller: $response->redirect();
```

<!-- snippet: output -->
```text
GET
sign1=U8BhKPlggZUOhAmr5OTr61Cj8p0cW9TF
```

Pred presmerovaním si objednávku uložte aj so sumou a symbolmi: návratová URL ich bude potrebovať. Pozrite [Začatie platby](./purchase).

## 2. Overte návrat {#_2-verify-the-return}

SporoPay pošle zákazníka na vašu návratovú URL so všetkými poľami platby, účtom platiteľa a parametrami `result`, `real` a `SIGN2` v query stringu. Zavolajte `completePurchase()` s variabilným symbolom a sumou objednávky, ktorú očakávate:

```php
use Omnipay\Common\Exception\InvalidRequestException;

try {
    $response = $gateway->completePurchase(['vs' => '2024000123', 'amount' => '10.00', 'ss' => '0000000001'])->send();
} catch (InvalidRequestException $e) {
    // forged or tampered return, or the return of another payment: do not touch the order
    exit('Invalid return: ' . $e->getMessage());
}

if ($response->isSuccessful()) {
    echo "Paid {$response->getAmount()} {$response->getCurrency()}, vs {$response->getVs()}\n";
} else {
    echo "Not paid: {$response->getRes()}\n";   // NOK: offer to pay again
}
```

<!-- snippet: output -->
```text
Paid 10.00 EUR, vs 2024000123
```

SporoPay podpisuje všetky polia návratu, takže `completePurchase()` overí voči vašej objednávke aj sumu a menu. Pozrite [Návratová URL](./complete-purchase).

## Ďalšie kroky {#next-steps}

- [Priebeh platby](./payment-flow): čo sa deje medzi e-shopom, prehliadačom a bankou
- [Konfigurácia](./configuration): všetky parametre
- [Príklady](../examples/): spustiteľné recepty vrátane e-shopu a falošnej banky na localhoste
- [Testovací režim](./test-mode): simulátor platieb
