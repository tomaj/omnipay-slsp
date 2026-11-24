# Signatures with openssl

`sign1` and `SIGN2` computed twice: by the library's `Des3Sign` and by the `openssl` command line (`openssl dgst -sha1` and `openssl enc -des-ede3-cbc`), with the simulator's 16-byte key and a 24-byte key. Then shared secrets the library rejects: not strict base64, empty, or a key of the wrong length.

**Shows:** the exact signed strings, the key handling (`K1 K2 K1` for a 16-byte key), `Exception\InvalidArgumentException`.

<<< @/../examples/signatures/run.php{php}

## Output

<<< @/../examples/signatures/expected-output.txt{text}

## Notes

- A secret without the `=` padding is accepted (PHP's strict base64 decoding allows it) and gives the same key.
- An empty secret and an 8-byte key (`MTIzNDU2Nzg=` is `12345678`) are rejected: OpenSSL would pad a short key with zero bytes, so an empty secret would sign with an all-zero key.
- See [Signatures](../guide/signatures) for the algorithm and a shell function that does the same.
