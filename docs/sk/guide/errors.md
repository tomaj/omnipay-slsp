# Spracovanie chýb

Brána hlási problémy výnimkami; platba, ktorá neprešla, nie je výnimka, ale odpoveď s `isSuccessful() === false`.

## Čo môže byť vyhodené {#what-can-be-thrown}

| Výnimka | Vyhodí ju | Kedy |
|---|---|---|
| `Omnipay\Common\Exception\InvalidRequestException` | `purchase()->send()`, `getData()` | povinný parameter je `null` (`The pu_predcislo parameter is required`, `... pu_cislo ...`, `... vs ...`, `... ss ...`, `... rurl ...`, `... param ...`, `... amount ...`), chybný formát účtu, platobného symbolu, návratovej URL alebo `param` (`The pu_predcislo parameter must be up to 6 digits`, `The pu_cislo parameter must be up to 10 digits`, `The vs parameter must be up to 10 digits`, `... ss ...`, `The rurl parameter must be an absolute https:// URL`, `The param parameter must not contain control characters or ";"`), neplatná suma (`Amount precision is too high for currency.`, `A negative amount is not allowed.`), `sendData()` zavolané s niečím iným ako pole (`Purchase data must be an array`) |
| `Omnipay\Common\Exception\InvalidRequestException` | `completePurchase()->send()`, `getData()` | `The vs parameter is required`, `The amount parameter is required`, `incorect signature`, `Variable symbol mismatch`, `Amount mismatch`, `Currency mismatch`, `Specific symbol mismatch` ([kontroly](./complete-purchase#the-checks)) |
| `Omnipay\SporoPay\Exception\InvalidArgumentException` | `Des3Sign::sign()`, teda obe `send()` | `Shared secret must not be empty`, `Shared secret must be base64 encoded`, `Shared secret must be a 16- or 24-byte 3DES key` |
| `Omnipay\SporoPay\Exception\RuntimeException` | `Des3Sign::sign()` | `Unable to compute the DES-EDE3-CBC signature: ...` (OpenSSL zlyhal) |
| `Omnipay\SporoPay\Exception\LogicException` | `PurchaseResponse::getRedirectUrl()` | odpoveď bola vytvorená pre inú požiadavku ako `PurchaseRequest` (chyba v programe) |
| `Omnipay\Common\Exception\RuntimeException` | Omnipay, `initialize()` a settery | požiadavka sa zmenila po `send()` (`Request cannot be modified after it has been sent!`) |

Každá výnimka, ktorú balík definuje, implementuje `Omnipay\SporoPay\Exception\SporoPayException`, ktoré rozširuje `OmnipayException` z Omnipay; triedy navyše dedia od rovnomennej SPL výnimky. `InvalidRequestException` aj `RuntimeException` z Omnipay implementujú `OmnipayException` tiež, takže jeden `catch` zachytí všetko, čo brána vyhodí:

```php
use Omnipay\Common\Exception\OmnipayException;

try {
    $response = $gateway->completePurchase(['vs' => '2024000123', 'amount' => '100.00'])->send();
} catch (OmnipayException $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}
```

<!-- snippet: output -->
```text
Omnipay\Common\Exception\InvalidRequestException: Amount mismatch
```

## Na návratovej URL {#on-the-return-url}

Každá `InvalidRequestException` z `completePurchase()` znamená pre objednávku to isté: **táto požiadavka nepreukazuje platbu tejto objednávky v tejto sume**. Objednávku nemeňte, odpovedzte chybovou stránkou (400 je v poriadku) a správu zalogujte spolu s query stringom; pomôže to odlíšiť chybnú konfiguráciu od útoku:

| Správa | Obvyklá príčina |
|---|---|
| `incorect signature` pri každom návrate | nesprávny `sharedSecret` (kľúč simulátora v produkcii alebo naopak), alebo router, ktorý query string mení alebo ho dekóduje dvakrát |
| `incorect signature` občas | niekto upravuje URL alebo otvára návratovú URL bez parametrov |
| `Variable symbol mismatch` | návrat inej objednávky, alebo sa objednávka načítaná zo session líši od tej, ktorú zákazník zaplatil v inej karte |
| `Amount mismatch` | návrat inej (lacnejšej) platby, alebo sa suma objednávky po presmerovaní zmenila |
| `Currency mismatch` | podpísaný návrat s inou menou ako `EUR`; pri SporoPay by sa to nemalo stať |
| `Specific symbol mismatch` | `ss` odovzdaný do `completePurchase()` sa líši od toho, ktorý bol poslaný v `purchase()` |
| `The vs parameter is required`, `The amount parameter is required` | chyba vo vašej návratovej URL: nenačítala sa žiadna objednávka |

`Exception\InvalidArgumentException` alebo `Exception\RuntimeException` na návratovej URL je problém konfigurácie (poškodený kľúč, nefunkčné OpenSSL), nie zlý návrat: upozornite seba, nie zákazníka.

## V pokladni {#in-the-checkout}

Výnimky z `purchase()` sú chyby konfigurácie alebo programu: chýbajúca návratová URL, suma alebo symbol, suma s tromi desatinnými miestami, poškodený kľúč. Nechajte ich dôjsť k vášmu error handleru, alebo objednávku overte skôr, než zavoláte bránu.
