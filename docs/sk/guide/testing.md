# Testovanie integrácie

SporoPay nemá žiadne volania zo servera na server, takže všetko, čo robí banka, sa dá v teste zopakovať: platobná požiadavka je podpísaná URL, návrat je podpísaný query string. Potrebujete zdieľaný kľúč z testovacej konfigurácie a `openssl_encrypt()`.

## Unit test návratovej URL {#unit-test-the-return-url}

Vytvorte návrat z banky ako Symfony požiadavku a odovzdajte ho bráne. V teste ho podpíšte priamo cez `openssl_encrypt()`, nie knižnicou, aby test overoval knižnicu, a nie ju len opakoval:

```php
use Omnipay\SporoPay\Gateway;
use Symfony\Component\HttpFoundation\Request;

$secret = 'Z3qY08EpvLlAAoMZdnyUdQ==';
$bankReturn = static function (array $change = []) use ($secret): Request {
    $query = array_replace([
        'u_predcislo' => '000000', 'u_cislo' => '1234567890', 'u_kbanky' => '0900',
        'pu_predcislo' => '000000', 'pu_cislo' => '0013662162', 'pu_kbanky' => '0900',
        'suma' => '10.00', 'mena' => 'EUR', 'vs' => '2024000123', 'ss' => '0000000001',
        'url' => 'https://shop.example/return', 'param' => 'order=2024000123', 'result' => 'OK', 'real' => 'OK',
    ], $change);
    $key = base64_decode($secret, true);
    $hash = sha1(implode(';', $query), true) . str_repeat("\xFF", 4);
    $query['SIGN2'] = base64_encode(openssl_encrypt($hash, 'DES-EDE3-CBC', $key . substr($key, 0, 8), OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, str_repeat("\0", 8)));

    return Request::create('https://shop.example/return', 'GET', $query);
};

$gateway = new Gateway(null, $bankReturn(['result' => 'NOK', 'real' => 'NOK']));
$gateway->setSharedSecret($secret);
$response = $gateway->completePurchase(['vs' => '2024000123', 'amount' => '10.00'])->send();

echo var_export($response->isSuccessful(), true) . ' ' . $response->getRes() . "\n";
```

<!-- snippet: output -->
```text
false NOK
```

Pokryte aspoň: `OK`, `NOK`, upravený `result`, návrat nižšej sumy, návrat inej objednávky, návratovú URL bez parametrov a ten istý návrat `OK` dvakrát. [Príklad Návratová URL](../examples/return-url) prežene presne tieto prípady jedným handlerom.

## Unit test pokladne {#unit-test-the-checkout}

Overujte `getRedirectData()` (alebo rozparsujte `getRedirectUrl()`) a očakávaný `sign1` vypočítajte nezávisle, raz, cez príkazový riadok `openssl` ([Podpisy](./signatures#verify-with-openssl)). Testovací vektor, ktorý si vypočítala sama knižnica, by nezachytil zlé poradie polí.

```php
$data = $gateway->purchase([
    'accountNumberPrefix' => '000000', 'accountNumber' => '0013662162', 'amount' => '10.00',
    'vs' => '2024000123', 'ss' => '0000000001', 'rurl' => 'https://shop.example/return', 'param' => 'order=2024000123',
])->send()->getRedirectData();

// openssl, see Signatures
echo $data['sign1'] === 'U8BhKPlggZUOhAmr5OTr61Cj8p0cW9TF' ? "signature ok\n" : "signature changed\n";
```

<!-- snippet: output -->
```text
signature ok
```

## End-to-end bez banky {#end-to-end-without-a-bank}

[Príklad celého priebehu s presmerovaním](../examples/full-flow) spustí váš e-shop a malú falošnú banku cez `php -S` a skript hrá úlohu prehliadača: otvorí pokladňu, nasleduje presmerovanie do banky a presmerovanie banky späť. V CI to bez siete trvá pár sekúnd. Skopírujte si `bank/index.php` do vlastných end-to-end testov a nasmerujte naň `testHost`.

## Ručný test so simulátorom {#manual-test-with-the-simulator}

Zapnite [testovací režim](./test-mode) s kľúčom simulátora a zaplaťte v prehliadači: `https://platby.tomaj.sk` (alebo vaša vlastná inštancia) ukáže prijatú požiadavku, či sedí `sign1`, a ponúkne odkazy späť na vašu návratovú URL pre každý výsledok.

## Pred spustením do produkcie {#before-going-live}

- [ ] produkčný zdieľaný kľúč sa načítava z premenných prostredia a testovací režim je vypnutý;
- [ ] predčíslie a číslo účtu patria vášmu produkčnému účtu v Slovenskej sporiteľni;
- [ ] návratová URL je z prehliadača zákazníka dostupná cez HTTPS (banka na ňu len presmeruje, sama ju nevolá);
- [ ] pri skutočnej platbe malej sumy vráti `isSuccessful()` hodnotu `true` a objednávka sa označí ako zaplatená práve raz;
- [ ] odmietnutá platba (`NOK`) nechá objednávku nezaplatenú a zákazník môže zaplatiť znova;
- [ ] objednávky, ktorých zákazník sa už nevrátil, sa párujú s bankovým výpisom.
