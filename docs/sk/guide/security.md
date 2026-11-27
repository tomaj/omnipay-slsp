# Bezpečnosť

omnipay-slsp podpisuje vaše platobné požiadavky a overuje návraty z banky. Táto stránka popisuje, pred čím vás to chráni, pred čím nie a čo zostáva na vašom e-shope. Zraniteľnosť nahláste podľa [bezpečnostnej politiky](https://github.com/tomaj/omnipay-slsp/security/policy): súkromne, nie vo verejnom issue.

## Zdieľaný kľúč {#the-shared-secret}

- Zdieľaný kľúč (`sharedSecret`) podpisuje požiadavky a overuje návraty. Kto ho má, dokáže podvrhnúť návrat `OK`. Držte ho v premenných prostredia alebo v úložisku tajomstiev; necommitujte ho, nelogujte ho a neposielajte ho do prehliadača.
- Prázdny kľúč alebo kľúč, ktorý sa nedekóduje na 16 alebo 24 bajtov, brána odmietne: `purchase()` aj `completePurchase()` vyhodia `Exception\InvalidArgumentException` namiesto toho, aby podpisovali kľúčom, ktorý OpenSSL doplní nulovými bajtmi (z prázdneho kľúča by bol samé nuly, ktoré vie vypočítať ktokoľvek). Konfiguráciu aj tak skontrolujte pri štarte aplikácie, aby chýbajúci kľúč zastavil nasadenie, a nie až prvú platbu.
- Kľúč simulátora (`Z3qY08EpvLlAAoMZdnyUdQ==`) je verejný. Používajte ho len v testovacom režime.

### Výpisy zásobníka {#stack-traces}

Chybové hlásenia (Tracy, Sentry, Monolog, `getTraceAsString()`) ukazujú argumenty každého volania na zásobníku. Parametre, ktoré preberajú kľúč, sú označené atribútom `#[\SensitiveParameter]`, takže PHP namiesto kľúča vypíše `Object(SensitiveParameterValue)`: `setSharedSecret()` v triedach `Gateway`, `PurchaseRequest` a `CompletePurchaseRequest` a `Des3Sign::sign()` (ktorá pri prázdnom alebo chybnom kľúči vyhodí výnimku, kým je kľúč na zásobníku).

Na polia sa to nevzťahuje: kľúč odovzdaný cez `initialize(['sharedSecret' => ...])` alebo `purchase(['sharedSecret' => ...])` sa vypíše spolu s poľom. Nastavte ho cez `setSharedSecret()` a v produkcii vypnite argumenty vo výpisoch nastavením `zend.exception_ignore_args=On` v `php.ini` (predvolená hodnota v `php.ini-production`).

## Čo `completePurchase()` zaručuje {#what-completepurchase-guarantees}

Keď skončí bez výnimky:

- tento návrat vytvorila banka (alebo ten, kto má zdieľaný kľúč): `SIGN2` pokrýva všetky polia a bol porovnaný v konštantnom čase (`hash_equals()`);
- návrat patrí k variabilnému symbolu, ktorý ste odovzdali, k sume, ktorú ste odovzdali, a k mene `EUR`, a ak ste odovzdali špecifický symbol, tak aj k nemu.

## Čo zaručiť nemôže {#what-it-cannot-guarantee}

- **Čerstvosť.** Návrat nemá časovú pečiatku ani nonce; tá istá URL sa overí znova a znova. Označenie „zaplatené“ musí byť idempotentné a vedľajšie efekty (odoslanie tovaru, e-maily) sa nesmú spustiť dvakrát.
- **`param`, váš účet a návratovú URL.** Sú podpísané, ale `completePurchase()` ich s ničím neporovnáva. Ak sa na `param` spoliehate, porovnajte ho sami.
- **Že návrat vôbec príde.** Zákazník môže po zaplatení zatvoriť prehliadač. Nezaplatené objednávky párujte s bankovým výpisom.

## Suma {#amount}

Na rozdiel od niektorých iných platobných tlačidiel slovenských bánk podpisuje SporoPay v návrate aj sumu a `completePurchase()` vyžaduje sumu objednávky a porovná ju. Odovzdajte sumu uloženú pri objednávke, nikdy nie sumu prečítanú z požiadavky.

## Testovací režim {#test-mode}

Testovací režim mení len to, kam sa zákazník presmeruje. Verejný kľúč simulátora nikdy nepoužívajte v produkcii a `testMode` aj kľúč držte v tej istej konfigurácii prostredia.

## Prenos {#transport}

Pokladňu aj návratovú URL prevádzkujte cez HTTPS. Návrat nenesie žiadne tajomstvo, ale cez obyčajné HTTP by ho ktokoľvek v sieti mohol vidieť a zopakovať.

## Nahlasovanie {#reporting}

Zraniteľnosti nahlasujte cez [súkromné nahlásenie zraniteľnosti na GitHube](https://github.com/tomaj/omnipay-slsp/security/advisories/new). [Bezpečnostná politika](https://github.com/tomaj/omnipay-slsp/security/policy) uvádza podporované verzie.
