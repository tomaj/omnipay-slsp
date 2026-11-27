# Podpisy

SporoPay podpisuje cez 3DES:

1. polia sa spoja znakom `;` (prázdne pole aj tak pridá svoj oddeľovač). Žiadne pole nesmie obsahovať `;`, inak by sa reťazec dal rozdeliť na polia viacerými spôsobmi: `purchase()` odmietne `rurl` alebo `param` so `;` a `completePurchase()` odmietne návrat, ktorý má `;` v ľubovoľnom poli;
2. z tohto reťazca sa vypočíta SHA-1 (20 bajtov) a doplní sa na 24 bajtov hodnotou `0xFF`;
3. výsledok sa zašifruje cez DES-EDE3-CBC s nulovým IV a bez zarovnania; kľúčom je zdieľaný kľúč dekódovaný z base64: 16-bajtový kľúč `K1 K2` sa použije ako `K1 K2 K1`, 24-bajtový tak, ako je;
4. zakóduje sa do base64 (32 znakov).

## Platobná požiadavka: `sign1` {#payment-request-sign1}

```
pu_predcislo;pu_cislo;pu_kbanky;suma;mena;vs;ss;url;param
```

Pre [príklad Presmerovanie na platbu](../examples/purchase-redirect) dá reťazec `000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return;order=2024000123` s kľúčom simulátora `Z3qY08EpvLlAAoMZdnyUdQ==` podpis `U8BhKPlggZUOhAmr5OTr61Cj8p0cW9TF`.

## Návrat: `SIGN2` {#return-sign2}

```
u_predcislo;u_cislo;u_kbanky;pu_predcislo;pu_cislo;pu_kbanky;suma;mena;vs;ss;url;param;result;real
```

`u_*` je účet platiteľa, `pu_*` váš. Podpísané sú všetky polia návratu vrátane sumy (`suma`) a meny (`mena`). `completePurchase()` vypočíta podpis z prijatých hodnôt, porovná ho so `SIGN2` cez `hash_equals()` a potom overí `vs`, `suma`, `mena` a (voliteľne) `ss` voči objednávke, pozrite [Návratová URL](./complete-purchase#the-checks).

## Overenie cez openssl {#verify-with-openssl}

```bash
sign() {
  key=$(printf '%s' "$2" | base64 -d | xxd -p | tr -d '\n')
  [ ${#key} -eq 32 ] && key="$key${key:0:16}"   # 16-byte key: K1 K2 K1
  { printf '%s' "$1" | openssl dgst -sha1 -binary; printf '\xff\xff\xff\xff'; } \
    | openssl enc -des-ede3-cbc -K "$key" -iv 0000000000000000 -nopad | base64
}

sign '000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return;order=2024000123' 'Z3qY08EpvLlAAoMZdnyUdQ=='
# U8BhKPlggZUOhAmr5OTr61Cj8p0cW9TF
```

[Príklad Podpisy cez openssl](../examples/signatures) vypočíta `sign1` aj `SIGN2` knižnicou aj príkazovým riadkom `openssl` a porovná ich, pre 16-bajtový aj 24-bajtový kľúč.

## Trieda `Des3Sign` {#the-des3sign-class}

```php
use Omnipay\SporoPay\Sign\Des3Sign;

echo new Des3Sign()->sign('000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return;order=2024000123', 'Z3qY08EpvLlAAoMZdnyUdQ==') . "\n";
```

<!-- snippet: output -->
```text
U8BhKPlggZUOhAmr5OTr61Cj8p0cW9TF
```

`Des3Sign::sign(string $input, string $secret): string` vyhodí `Exception\InvalidArgumentException('Shared secret must not be empty')` pre prázdny kľúč alebo kľúč len z bielych znakov, `Exception\InvalidArgumentException('Shared secret must be a 16- or 24-byte 3DES key')`, keď má dekódovaný kľúč inú dĺžku, `Exception\InvalidArgumentException('Shared secret must be base64 encoded')`, keď kľúč nie je striktné base64 (biele znaky a chýbajúce zarovnanie `=` sú v poriadku; úvodzovky, `-` ani `_` nie), a `Exception\RuntimeException('Unable to compute the DES-EDE3-CBC signature: ...')`, keď OpenSSL nedokáže zašifrovať. `PurchaseRequest::generateSignature(string $data)` ju volá so zdieľaným kľúčom (`sharedSecret`) požiadavky.

::: tip OpenSSL 3
Triple DES (DES-EDE3-CBC) je súčasťou predvoleného providera OpenSSL 3; legacy provider nie je potrebný.
:::
