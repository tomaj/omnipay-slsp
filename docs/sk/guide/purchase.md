# Začatie platby

`purchase()` zostaví podpísanú platobnú požiadavku. Z vášho servera sa nič neposiela: SporoPay je presmerovanie cez GET, takže prehliadač zákazníka pošlete na URL, ktorá nesie všetky polia aj podpis `sign1`.

<<< @/../examples/purchase-redirect/run.php{php}

Výstup ([príklad Presmerovanie na platbu](../examples/purchase-redirect)):

<<< @/../examples/purchase-redirect/expected-output.txt{text}

## Požiadavka {#the-request}

`$gateway->purchase(array $parameters)` vráti `PurchaseRequest` s parametrami brány doplnenými o vaše ([všetky parametre](./configuration#purchase-parameters)). `send()` overí parametre, pridá `sign1` a vráti `PurchaseResponse`. Vyhodí:

| Výnimka | Kedy |
|---|---|
| `InvalidRequestException('The pu_predcislo parameter is required')`, `... pu_cislo ...`, `... vs ...`, `... ss ...`, `... rurl ...`, `... param ...`, `... amount ...` | parameter je `null` |
| `InvalidRequestException('The pu_predcislo parameter must be up to 6 digits')`, `('The pu_cislo parameter must be up to 10 digits')` | predčíslie účtu nemá 1 až 6 číslic alebo číslo účtu nemá 1 až 10 číslic (bez pomlčiek, medzier, kódu banky či IBAN) |
| `InvalidRequestException('The vs parameter must be up to 10 digits')`, `... ss ...` | `vs` alebo `ss` nemá 1 až 10 číslic (len `0` – `9`, bez medzier a znamienok) |
| `InvalidRequestException('The rurl parameter must be an absolute https:// URL')` | `rurl` nie je absolútna `https://` URL s hostiteľom (je relatívna, má inú schému alebo obsahuje medzery či riadiace znaky); v testovacom režime sa prijme aj `http://` a správa znie `... must be an absolute https:// or http:// URL` |
| `InvalidRequestException('The param parameter must not contain control characters')` | `param` obsahuje zalomenie riadka, tabulátor, NUL alebo iný riadiaci znak |
| `InvalidRequestException('Amount precision is too high for currency.')` | `amount` má viac ako dve desatinné miesta |
| `InvalidRequestException('A negative amount is not allowed.')` | `amount` je záporná |
| `Exception\InvalidArgumentException('Shared secret must not be empty')` | zdieľaný kľúč je prázdny alebo len z bielych znakov (predvolená hodnota brány je `''`) |
| `Exception\InvalidArgumentException('Shared secret must be base64 encoded')` | zdieľaný kľúč nie je striktné base64 |
| `Exception\InvalidArgumentException('Shared secret must be a 16- or 24-byte 3DES key')` | dekódovaný zdieľaný kľúč má inú dĺžku |
| `Exception\RuntimeException('Unable to compute the DES-EDE3-CBC signature: ...')` | OpenSSL nedokázal zašifrovať |

Výnimky `InvalidRequestException` vyhodí už `getData()`, skôr než sa čokoľvek podpíše, takže chybne zadaná platba sa nikdy nedostane na chybovú stránku SporoPay. Prijímané formáty:

| Parameter | Formát |
|---|---|
| `accountNumberPrefix` (`pu_predcislo`) | 1 až 6 číslic (`000000` bez predčíslia) |
| `accountNumber` (`pu_cislo`) | 1 až 10 číslic |
| `vs`, `ss` | 1 až 10 číslic |
| `rurl` | absolútna `https://` URL s hostiteľom, bez medzier a riadiacich znakov; `http://` len v testovacom režime |
| `param` | ľubovoľný text bez riadiacich znakov, vrátane `''` |

`getData()` vráti polia bez podpisu v tomto poradí: `pu_predcislo`, `pu_cislo`, `pu_kbanky`, `suma`, `mena`, `vs`, `ss`, `url`, `param`. `sendData($data)` pridá `sign1`.

## Odpoveď {#the-response}

| Metóda | Vráti |
|---|---|
| `isSuccessful()` | `false`: platba ešte neprebehla |
| `isRedirect()` | `true` |
| `getRedirectMethod()` | `'GET'` |
| `getRedirectUrl()` | endpoint s poliami a `sign1` v query stringu (`http_build_query()`, takže pole s hodnotou `null` sa vynechá) |
| `getRedirectData()` | polia z `getData()` plus `sign1` |
| `redirect()` | pošle presmerovanie 302 na `getRedirectUrl()` (Omnipay) |
| `getRedirectResponse()` | to isté presmerovanie ako Symfony `RedirectResponse`, pre frameworky, ktoré vracajú objekt odpovede |

`redirect()` pošle hlavičky a malú HTML stránku cez `echo`; zavolajte ho v controlleri a nič iné už nevypisujte. Vo frameworkoch vráťte `getRedirectResponse()` alebo presmerujte na `getRedirectUrl()` vlastným presmerovaním frameworku.

Endpoint je `https://ib.slsp.sk/epayment/epayment/epayment.xml`, alebo simulátor v [testovacom režime](./test-mode); `PurchaseRequest::getEndpoint()` ho vráti bez query stringu.

## Výber symbolov {#choosing-the-symbols}

- **`vs`** je to, podľa čoho návrat nájde svoju objednávku, a `completePurchase()` ho kontroluje. Variabilný symbol by mal byť jedinečný pre každú objednávku (a pre každý pokus o platbu, ak to vyžaduje vaše účtovníctvo), len z číslic, najviac 10.
- **`ss`** brána vyžaduje. Použite to, čo očakáva vaše účtovníctvo (číslo zákazníka, rok faktúry), alebo pevnú hodnotu; odovzdajte ho aj do `completePurchase()`, aby sa návrat voči nemu overil.
- **`param`** je voľný text, podpísaný v oboch smeroch a vrátený nezmenený. Hodí sa na vlastnú referenciu objednávky, brána ho však nekontroluje: ak sa naň spoliehate, porovnajte ho sami.

## Vlastné podpísanie reťazca {#signing-a-string-yourself}

`PurchaseRequest::generateSignature(string $data)` vráti 3DES podpis ľubovoľného reťazca so zdieľaným kľúčom (`sharedSecret`) požiadavky, rovnako ako `new Des3Sign()->sign($data, $secret)` ([Podpisy](./signatures)).
