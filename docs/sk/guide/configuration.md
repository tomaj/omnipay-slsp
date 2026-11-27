# Konfigurácia

Parametre nastavíte na bráne, kde sa stanú predvolenými hodnotami každej požiadavky, alebo ich odovzdáte v poli `purchase([...])` / `completePurchase([...])`, kde pre danú požiadavku prepíšu hodnoty z brány. Každý parameter má na požiadavke aj typovaný setter. Omnipay mapuje kľúče poľa na settery, takže `accountNumberPrefix` aj `account_number_prefix` zavolajú `setAccountNumberPrefix()`.

```php
use Omnipay\SporoPay\Gateway;

$gateway = new Gateway();
$gateway->setSharedSecret(getenv('SPOROPAY_SHARED_SECRET') ?: 'Z3qY08EpvLlAAoMZdnyUdQ==');
$gateway->setTestMode(false);

// the same with initialize(), for example from a configuration array
$gateway->initialize([
    'sharedSecret' => getenv('SPOROPAY_SHARED_SECRET') ?: 'Z3qY08EpvLlAAoMZdnyUdQ==',
    'testMode' => false,
]);

var_dump($gateway->getSharedSecret(), $gateway->getTestMode());
```

<!-- snippet: output -->
```text
string(24) "Z3qY08EpvLlAAoMZdnyUdQ=="
bool(false)
```

::: tip initialize() resetuje
Ako v každej Omnipay bráne, `initialize()` najprv vráti všetky parametre na predvolené hodnoty a až potom použije pole.
:::

## Parametre brány {#gateway-parameters}

| Parameter | Setter / getter | Typ | Predvolená hodnota | Popis |
|---|---|---|---|---|
| `sharedSecret` | `setSharedSecret()` / `getSharedSecret()` | `?string` | `''` | Zdieľaný kľúč: 3DES kľúč od Slovenskej sporiteľne zakódovaný v base64, po dekódovaní 16 alebo 24 bajtov. Podpisuje požiadavku a overuje návrat; nikam sa neposiela. Dekóduje sa striktne: biele znaky, zalomenia riadkov a chýbajúce zarovnanie `=` sú v poriadku, akýkoľvek iný znak mimo abecedy base64 vyhodí pri podpisovaní `Exception\InvalidArgumentException('Shared secret must be base64 encoded')`. Povinný: prázdny kľúč alebo kľúč len z bielych znakov (vrátane predvoleného `''`) vyhodí `Shared secret must not be empty`, kľúč inej dĺžky ako 16 alebo 24 bajtov `Shared secret must be a 16- or 24-byte 3DES key`, a to v `purchase()` aj `completePurchase()`. |
| `testMode` | `setTestMode()` / `getTestMode()` | `bool` | nenastavené (vypnuté) | `true` presmeruje na simulátor platieb namiesto SporoPay. Pozrite [Testovací režim](./test-mode). |
| `testHost` | `setTestHost()` / `getTestHost()` | `?string` | `''` (znamená `https://platby.tomaj.sk`) | Schéma a host simulátora v testovacom režime, bez lomky na konci, napríklad `http://127.0.0.1:8080`. |

::: warning Kľúč skontrolujte pri štarte
Predvolený `sharedSecret` brány je prázdny reťazec. Od verzie 4.0 každé `purchase()` aj `completePurchase()` vyhodí pri prázdnom kľúči alebo kľúči nesprávnej dĺžky `Exception\InvalidArgumentException`, namiesto toho, aby podpisovali kľúčom doplneným nulovými bajtmi. Konfiguráciu aj tak skontrolujte pri štarte aplikácie, napríklad cez `getenv('SPOROPAY_SHARED_SECRET') ?: throw new RuntimeException(...)`, aby pokazené nasadenie zlyhalo hneď, a nie až pri prvej platbe.
:::

## Parametre `purchase()` {#purchase-parameters}

| Parameter | Setter | Posiela sa ako | Povinný | Popis |
|---|---|---|---|---|
| `accountNumberPrefix` | `setAccountNumberPrefix()` | `pu_predcislo` | áno | Predčíslie vášho účtu v Slovenskej sporiteľni (`000000`, ak ho účet nemá), 1 až 6 číslic (kontroluje sa). |
| `accountNumber` | `setAccountNumber()` | `pu_cislo` | áno | Číslo vášho účtu, 1 až 10 číslic (kontroluje sa; bez pomlčiek, medzier a kódu banky). |
| | | `pu_kbanky` | | Vždy `0900`, kód banky Slovenskej sporiteľne (`PurchaseRequest::BANK_CODE`). |
| `amount` | `setAmount()` | `suma` | áno | Suma v eurách. Omnipay ju formátuje na dve desatinné miesta: `'10'`, `10` aj `'10.0'` sa pošlú ako `10.00`; viac ako dve desatinné miesta vyhodia `InvalidRequestException('Amount precision is too high for currency.')`, záporná suma `'A negative amount is not allowed.'`. Pozrite [príklad Formáty sumy](../examples/amount-formats). |
| | | `mena` | | Vždy `EUR` (`PurchaseRequest::CURRENCY`). |
| `vs` | `setVs()` | `vs` | áno | Variabilný symbol: identifikuje objednávku, len číslice, najviac 10 (kontroluje sa). Použite hodnotu jedinečnú pre každú objednávku. |
| `ss` | `setSs()` | `ss` | áno | Špecifický symbol, len číslice, najviac 10 (kontroluje sa). |
| `rurl` | `setRurl()` | `url` | áno | Návratová URL: kam banka pošle zákazníka späť s výsledkom. Absolútna `https://` URL (kontroluje sa); `http://` sa prijme len v testovacom režime. |
| `param` | `setParam()` | `param` | áno | Voľný text, ktorý sa vám vráti nezmenený a je podpísaný v oboch smeroch, napríklad `order=2024000123`. Nesmie obsahovať riadiace znaky ani `;` (kontroluje sa). |
| `sharedSecret`, `testMode`, `testHost` | | | | Zdedené z brány, pre jednotlivú požiadavku ich môžete prepísať. |

Všetky polia sú súčasťou `sign1`, pozrite [Podpisy](./signatures). Keď je povinný parameter `null`, `purchase()` vyhodí `InvalidRequestException` s názvom **posielaného** poľa: `The pu_predcislo parameter is required`, `The pu_cislo parameter is required`, `The vs parameter is required`, `The ss parameter is required`, `The rurl parameter is required`, `The param parameter is required`, `The amount parameter is required`. Chybný formát vyhodí výnimku skôr, než sa čokoľvek podpíše: `The pu_predcislo parameter must be up to 6 digits`, `The pu_cislo parameter must be up to 10 digits`, `The vs parameter must be up to 10 digits` (alebo `ss`), `The rurl parameter must be an absolute https:// URL` (v testovacom režime `... https:// or http:// URL`), `The param parameter must not contain control characters`.

Mena nie je parameter: SporoPay platí v eurách. Všeobecné parametre požiadavky v Omnipay (`currency`, `description`, `transactionId`, `returnUrl`, ...) brána prijme, ale banke ich neposiela; na návratovú URL použite `rurl`.

## Parametre `completePurchase()` {#completepurchase-parameters}

| Parameter | Typ | Povinný | Popis |
|---|---|---|---|
| `vs` | `?string` | áno | Variabilný symbol objednávky, ktorú dokončujete. Vrátený `vs` musí byť presne táto hodnota (`'0123'` nie je `'123'`). |
| `amount` | `string\|int\|float` | áno | Suma objednávky. Formátuje sa rovnako ako v `purchase()` (`10`, `'10.0'` aj `'10.00'` znamenajú `'10.00'`) a porovná sa s vrátenou `suma`. |
| `ss` | `?string` | nie | Ak je nastavený, vrátený `ss` musí byť presne táto hodnota. `null` kontrolu preskočí. |
| `sharedSecret` | `?string` | áno | Zvyčajne zdedený z brány. |

Vrátené polia sa čítajú z query stringu HTTP požiadavky, s ktorou bola brána vytvorená.

## HTTP požiadavka vo frameworkoch {#the-http-request-in-frameworks}

Omnipay dá bráne Symfony `Request`, predvolene `Request::createFromGlobals()`, čo je správne pre čisté PHP. Vo frameworku alebo na dlho bežiacom serveri (RoadRunner, FrankenPHP v režime worker, Swoole) vytvárajte bránu pre každú požiadavku s aktuálnou požiadavkou:

```php
use Omnipay\SporoPay\Gateway;
use Symfony\Component\HttpFoundation\Request;

$request = Request::create('https://shop.example/return', 'GET', ['vs' => '2024000123', 'result' => 'OK', 'SIGN2' => '...']);

$gateway = new Gateway(null, $request);                  // null: Omnipay discovers the HTTP client
// or: Omnipay::create('SporoPay', null, $request);

echo $gateway->getShortName() . "\n";
```

<!-- snippet: output -->
```text
SporoPay
```

`Illuminate\Http\Request` z Laravelu dedí od Symfony požiadavky, takže ho môžete odovzdať priamo; PSR-7 požiadavku skonvertujte cez `symfony/psr-http-message-bridge`.
