# Signatures

SporoPay signs with 3DES:

1. SHA-1 of the signed string (20 bytes), padded to 24 bytes with `0xFF`;
2. encrypted with DES-EDE3-CBC, a zero IV and no padding; the key is the base64-decoded shared secret (a 16-byte key `K1 K2` is used as `K1 K2 K1`, a 24-byte key as is);
3. base64 encoded.

Fields are joined with `;`; an empty field still adds its separator.

## Payment request (`sign1`)

```
pu_predcislo;pu_cislo;pu_kbanky;suma;mena;vs;ss;url;param
```

For the [example](./getting-started#first-payment): `000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return.php;order=2024000123` gives `nXibdJCwuNUjifaNVnqLs8oj8j/gsQI4` with the simulator's key `Z3qY08EpvLlAAoMZdnyUdQ==`.

## Return (`SIGN2`)

```
u_predcislo;u_cislo;u_kbanky;pu_predcislo;pu_cislo;pu_kbanky;suma;mena;vs;ss;url;param;result;real
```

`u_*` is the payer's account, `pu_*` yours. Every field of the return is signed, including the amount (`suma`) and the currency (`mena`). `completePurchase()` computes the signature from the received values, compares it with `SIGN2` using `hash_equals()`, and then checks `vs`, `suma`, `mena` and (optionally) `ss` against the order, see [Operations](./operations#completepurchase).

## Verify with openssl

```bash
sign() {
  key=$(printf '%s' "$2" | base64 -d | xxd -p | tr -d '\n')
  [ ${#key} -eq 32 ] && key="$key${key:0:16}"   # 16-byte key: K1 K2 K1
  { printf '%s' "$1" | openssl dgst -sha1 -binary; printf '\xff\xff\xff\xff'; } \
    | openssl enc -des-ede3-cbc -K "$key" -iv 0000000000000000 -nopad | base64
}

sign '000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return.php;order=2024000123' 'Z3qY08EpvLlAAoMZdnyUdQ=='
# nXibdJCwuNUjifaNVnqLs8oj8j/gsQI4
```

## The `Des3Sign` class

```php
use Omnipay\SporoPay\Sign\Des3Sign;

$sign1 = new Des3Sign()->sign('000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return.php;order=2024000123', $sharedSecret);
```

It throws `Exception\InvalidArgumentException` when the secret is not base64 and `Exception\RuntimeException` when OpenSSL cannot compute the signature. `PurchaseRequest::generateSignature(string $data)` does the same with the request's `sharedSecret`.
