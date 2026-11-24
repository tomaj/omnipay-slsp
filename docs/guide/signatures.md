# Signatures

SporoPay signs with 3DES:

1. the fields are joined with `;` (an empty field still adds its separator);
2. SHA-1 of that string (20 bytes), padded to 24 bytes with `0xFF`;
3. encrypted with DES-EDE3-CBC, a zero IV and no padding; the key is the base64-decoded shared secret: a 16-byte key `K1 K2` is used as `K1 K2 K1`, a 24-byte key as it is;
4. base64 encoded (32 characters).

## Payment request: `sign1`

```
pu_predcislo;pu_cislo;pu_kbanky;suma;mena;vs;ss;url;param
```

For the [Payment redirect example](../examples/purchase-redirect), `000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return;order=2024000123` with the simulator's key `Z3qY08EpvLlAAoMZdnyUdQ==` gives `U8BhKPlggZUOhAmr5OTr61Cj8p0cW9TF`.

## Return: `SIGN2`

```
u_predcislo;u_cislo;u_kbanky;pu_predcislo;pu_cislo;pu_kbanky;suma;mena;vs;ss;url;param;result;real
```

`u_*` is the payer's account, `pu_*` yours. Every field of the return is signed, including the amount (`suma`) and the currency (`mena`). `completePurchase()` computes the signature from the received values, compares it with `SIGN2` using `hash_equals()`, and then checks `vs`, `suma`, `mena` and (optionally) `ss` against the order, see [The return URL](./complete-purchase#the-checks).

## Verify with openssl

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

The [Signatures with openssl example](../examples/signatures) computes `sign1` and `SIGN2` with the library and with the `openssl` command line and compares them, for a 16-byte and a 24-byte key.

## The `Des3Sign` class

```php
use Omnipay\SporoPay\Sign\Des3Sign;

echo new Des3Sign()->sign('000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return;order=2024000123', 'Z3qY08EpvLlAAoMZdnyUdQ==') . "\n";
```

<!-- snippet: output -->
```text
U8BhKPlggZUOhAmr5OTr61Cj8p0cW9TF
```

`Des3Sign::sign(string $input, string $secret): string` throws `Exception\InvalidArgumentException('Shared secret must be base64 encoded')` when the secret is not strict base64 (whitespace and a missing `=` padding are accepted; quotes, `-` or `_` are not) and `Exception\RuntimeException('Unable to compute the DES-EDE3-CBC signature: ...')` when OpenSSL cannot encrypt. `PurchaseRequest::generateSignature(string $data)` calls it with the request's `sharedSecret`.

::: tip OpenSSL 3
Triple DES (DES-EDE3-CBC) is part of OpenSSL 3's default provider; the legacy provider is not needed.
:::
