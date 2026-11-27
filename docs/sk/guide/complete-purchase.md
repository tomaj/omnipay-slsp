# Návratová URL

Po platbe SporoPay presmeruje zákazníka na vašu `url` so všetkými poľami v query stringu:

```
https://shop.example/return?u_predcislo=000000&u_cislo=1234567890&u_kbanky=0900&pu_predcislo=000000&pu_cislo=0013662162&pu_kbanky=0900&suma=10.00&mena=EUR&vs=2024000123&ss=0000000001&url=https%3A%2F%2Fshop.example%2Freturn&param=order%3D2024000123&result=OK&real=OK&SIGN2=HC85nauH3W8MH7JcaWKvPBCTk%2BGuydGv
```

`completePurchase()` z toho urobí overenú odpoveď pre jednu objednávku.

<<< @/../examples/return-url/run.php{php}

<<< @/../examples/return-url/expected-output.txt{text}

## Kontroly {#the-checks}

`$gateway->completePurchase(['vs' => ..., 'amount' => ..., 'ss' => ...])->send()` spustí tieto kontroly v tomto poradí. Prvá, ktorá zlyhá, vyhodí `InvalidRequestException` z Omnipay so správou z tabuľky:

| # | Kontrola | Správa |
|---|---|---|
| 1 | `vs` bol odovzdaný a nie je prázdny | `The vs parameter is required` |
| 2 | `amount` bola odovzdaná | `The amount parameter is required` |
| 3 | `SIGN2` sa rovná podpisu `u_predcislo;u_cislo;u_kbanky;pu_predcislo;pu_cislo;pu_kbanky;suma;mena;vs;ss;url;param;result;real` (porovnané cez `hash_equals()`); žiadne pole neobsahuje `;`, takže podpísaný reťazec sa dá rozdeliť len jedným spôsobom | `incorect signature` (sic) |
| 4 | vrátený `vs` je presne očakávaný `vs` | `Variable symbol mismatch` |
| 5 | vrátená `suma` je presne očakávaná `amount` naformátovaná na dve desatinné miesta | `Amount mismatch` |
| 6 | vrátená `mena` je `EUR` | `Currency mismatch` |
| 7 | ak bol odovzdaný `ss`: vrátený `ss` je presne táto hodnota | `Specific symbol mismatch` |

Výpočet podpisu môže vyhodiť aj `Exception\InvalidArgumentException` (kľúč nie je base64) alebo `Exception\RuntimeException` (OpenSSL zlyhal); obe sú chyby konfigurácie, nie zlý návrat.

Chýbajúci parameter v query stringu (alebo parameter, ktorý nie je reťazec, napríklad `vs[]=1`) sa číta ako `''`, takže požiadavka bez `SIGN2` zlyhá na kontrole 3. Kontrola podpisu beží pred kontrolami polí: návrat so zlým podpisom vždy hlási `incorect signature`.

## Odpoveď {#the-response}

Keď prejdú všetky kontroly, `send()` vráti `CompletePurchaseResponse`:

| Metóda | Vráti |
|---|---|
| `isSuccessful()` | `true`, keď je `result` rovné `OK` (`CompletePurchaseResponse::SUCCESS`) |
| `getRes()` | `'OK'` alebo `'NOK'` (podpísaný `result`) |
| `getVs()` | vrátený, overený variabilný symbol |
| `getSs()` | vrátený špecifický symbol (overený, ak ste odovzdali `ss`) |
| `getAmount()` | vrátená, overená suma, napríklad `'10.00'` |
| `getCurrency()` | vrátená, overená mena: `'EUR'` |
| `getReal()` | podpísané pole `real` tak, ako ho poslala banka (`'OK'` alebo `'NOK'`) |
| `getData()` | `['RES' => ..., 'VS' => ..., 'SS' => ..., 'AMOUNT' => ..., 'CURRENCY' => ..., 'REAL' => ...]` |
| `isRedirect()`, `isCancelled()` | `false` (predvolené v Omnipay) |
| `getMessage()`, `getCode()`, `getTransactionReference()` | `null` (predvolené v Omnipay) |

Účet platiteľa (`u_*`), váš účet (`pu_*`), `url` a `param` sú podpísané, ale odpoveď ich nevracia; ak ich potrebujete, prečítajte ich z požiadavky, až keď `send()` prebehlo úspešne.

## Spracovanie výsledku {#handling-the-result}

- **Výnimka**: požiadavku nevytvorila banka pre túto objednávku a sumu: je podvrhnutá, upravená alebo je to pravý návrat inej platby. Objednávku nemeňte. Zalogujte to a zákazníkovi ukážte stránku objednávky s jej skutočným stavom.
- **`isSuccessful()`**: označte objednávku ako zaplatenú, a to raz.
- **`NOK`**: zákazník nezaplatil. Ponúknite mu zaplatiť znova.

## O ktorú objednávku ide? {#which-order-is-it}

Objednávku načítajte buď zo session zákazníka, alebo podľa vráteného `vs` (`$request->query->get('vs')`), a do `completePurchase()` potom odovzdajte **jej** variabilný symbol a sumu:

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

## Odovzdajte správnu HTTP požiadavku {#pass-the-right-http-request}

Query string sa číta zo Symfony požiadavky, s ktorou bola brána vytvorená, nie z `$_GET`. Čisté PHP funguje bez ďalšieho nastavovania (`Request::createFromGlobals()`); vo frameworkoch a na dlho bežiacich serveroch odovzdajte aktuálnu požiadavku: `new Gateway(null, $request)`, pozrite [Konfigurácia](./configuration#the-http-request-in-frameworks).
