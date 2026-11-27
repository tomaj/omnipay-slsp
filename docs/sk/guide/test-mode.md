# Testovací režim

So zapnutým `testMode` presmeruje `purchase()` na simulátor platieb namiesto do SporoPay a prijme aj návratovú URL (`rurl`) s obyčajným `http://`, pre e-shop bežiaci na `localhost`; produkcia vyžaduje `https://`. Nič iné sa nemení: podpisy, návrat aj `completePurchase()` fungujú rovnako.

| Režim | `PurchaseRequest::getEndpoint()` |
|---|---|
| produkcia (`testMode` nenastavený alebo `false`) | `https://ib.slsp.sk/epayment/epayment/epayment.xml` |
| testovací režim | `https://platby.tomaj.sk/payment/sporopay-des` |
| testovací režim s `testHost` | `<testHost>/payment/sporopay-des` |

```php
$gateway->setTestMode(true);
echo $gateway->purchase()->getEndpoint() . "\n";

$gateway->setTestHost('http://127.0.0.1:8080');   // your own simulator
echo $gateway->purchase()->getEndpoint() . "\n";

$gateway->setTestMode(false);
echo $gateway->purchase()->getEndpoint() . "\n";
```

<!-- snippet: output -->
```text
https://platby.tomaj.sk/payment/sporopay-des
http://127.0.0.1:8080/payment/sporopay-des
https://ib.slsp.sk/epayment/epayment/epayment.xml
```

`testHost` sa používa len v testovacom režime; prázdny `testHost` znamená `https://platby.tomaj.sk`.

## Simulátor platieb {#payment-simulator}

[tomaj/payment-simulator](https://github.com/tomaj/payment-simulator) implementuje endpoint SporoPay (aj ďalšie platobné tlačidlá slovenských bánk). Podpisuje zdieľaným kľúčom `Z3qY08EpvLlAAoMZdnyUdQ==`, ukáže, či váš `sign1` sedí, a ponúkne odkazy späť na vašu návratovú URL s podpísaným výsledkom `OK` alebo `NOK`. `https://platby.tomaj.sk` je verejná inštancia; pre CI alebo súkromné dáta si spustite vlastnú v Dockeri a nasmerujte na ňu `testHost`.

## Falošná banka na localhoste {#a-fake-bank-on-localhost}

Na automatické testy simulátor nepotrebujete: SporoPay sú dve podpísané presmerovania, ktoré zvládne zahrať pár riadkov PHP. [Príklad celého priebehu s presmerovaním](../examples/full-flow) spustí e-shop a falošnú banku cez `php -S` a ovláda ich ako prehliadač; [Testovanie integrácie](./testing) ukazuje unit testy úplne bez servera.

## Nikdy v produkcii {#never-in-production}

Kľúč simulátora je verejný: e-shop, ktorý ním overuje návraty, prijme návrat `OK`, ktorý si môže podpísať ktokoľvek. Kľúč simulátora používajte len spolu s testovacím režimom a kľúč aj režim načítajte z premenných prostredia, aby ich produkčné nasadenie nemohlo omylom prevziať.
