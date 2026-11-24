# Časté otázky

## Prečo `completePurchase()` vyhodí „The vs parameter is required“ alebo „The amount parameter is required“?

Od verzie 4.0 odovzdávate variabilný symbol a sumu objednávky, ktorú dokončujete: `completePurchase(['vs' => $order->vs, 'amount' => $order->amount])`. Zabráni to tomu, aby platný návrat jednej objednávky alebo nižšej sumy dokončil inú. Pozrite [Prechod z 3.x](./upgrade).

## „Amount mismatch“, hoci zákazník zaplatil správnu sumu

Suma sa porovnáva ako naformátovaný text. Omnipay naformátuje vašu `amount` na dve desatinné miesta (z `10` je `'10.00'`) a banka vráti `suma` tak, ako ju poslalo `purchase()`. Do oboch odovzdajte tú istú sumu a dajte pozor, aby sa suma objednávky po presmerovaní nezmenila. Pozrite [príklad Formáty sumy](./examples/amount-formats).

## „incorect signature“ pri každom návrate

- `sharedSecret` sa líši od kľúča, ktorým bol návrat podpísaný (testovací režim používa kľúč simulátora, produkcia kľúč od banky);
- váš router mení query string alebo ho dekóduje dvakrát (`SIGN2` je base64 a môže obsahovať `+` a `/`);
- vo frameworku alebo na dlho bežiacom serveri číta brána inú požiadavku než aktuálnu: odovzdajte ju, `new Gateway(null, $request)`.

Správa je v kóde napísaná ako `incorect`; rozhodujte podľa triedy výnimky, nie podľa textu.

## „Shared secret must not be empty“ alebo „must be a 16- or 24-byte 3DES key“

Kľúč je prázdny (zvyčajne chýba premenná prostredia v danom nasadení) alebo sa nedekóduje na 3DES kľúč. Od verzie 4.0 ním brána odmieta podpisovať aj overovať: OpenSSL by ho doplnil nulovými bajtmi a z prázdneho kľúča by bol kľúč zo samých núl, ktorý vie vypočítať ktokoľvek. Nastavte base64 kľúč od Slovenskej sporiteľne (alebo kľúč simulátora v testovacom režime).

## „Shared secret must be base64 encoded“

Od verzie 4.0 sa kľúč dekóduje striktne. Odstráňte úvodzovky alebo iné znaky okolo hodnoty v konfigurácii a použite štandardnú abecedu base64 (`+` a `/`, nie `-` a `_`).

## Zákazník sa vráti bez parametrov návratu

Potom chýba `SIGN2` a `completePurchase()` vyhodí `InvalidRequestException('incorect signature')`. Ukážte zákazníkovi objednávku s možnosťou zaplatiť znova.

## Čo je `real`?

Ďalšie stavové pole návratu z banky, `OK` alebo `NOK`, pokryté podpisom `SIGN2`. Vracia ho `getReal()`; `isSuccessful()` sa pozerá len na `result`.

## Existuje notifikácia zo servera na server?

Nie. SporoPay zákazníka len presmeruje späť. Ak zákazník po zaplatení zatvorí prehliadač, váš e-shop sa o platbe dozvie z bankového výpisu.

## Môžem vrátiť platbu, zachytiť ju alebo sa spýtať na jej stav?

Nie. SporoPay je platobné tlačidlo: `purchase()` a `completePurchase()`. Vrátenie peňazí sa robí bankovým prevodom.

## Aké meny sú podporované?

Len euro: `mena` je vždy `EUR` a návrat s inou menou sa odmietne.

## Môžem bránu používať bez HTTP klienta Omnipay?

Brána nerobí žiadne HTTP požiadavky, ale konštruktor Omnipay vyhľadáva PSR-18 klienta. Nainštalujte nejaký (`php-http/guzzle7-adapter`) alebo konštruktoru odovzdajte vlastný `Omnipay\Common\Http\ClientInterface`.

## Ako ju použijem v Laraveli, Symfony alebo Nette?

Bránu vytvárajte pre každú požiadavku a odovzdajte jej aktuálnu HTTP požiadavku (Symfony `Request`; požiadavka v Laraveli od neho dedí). V akcii pokladne zavolajte `redirect()` alebo vráťte `getRedirectResponse()`, v akcii návratu `completePurchase()`. Pozrite [Konfigurácia](./guide/configuration#the-http-request-in-frameworks).

## Prečo sú triedy `final`?

Aby sa kód podpisovania a overovania nedal omylom zmeniť v podtriede. Bránu namiesto dedenia obaľte vlastnou službou; pozrite [Prechod z 3.x](./upgrade#final-classes).
