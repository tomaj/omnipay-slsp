# Podpisy cez openssl

`sign1` a `SIGN2` vypočítané dvakrát: triedou `Des3Sign` z knižnice a príkazovým riadkom `openssl` (`openssl dgst -sha1` a `openssl enc -des-ede3-cbc`), so 16-bajtovým kľúčom simulátora aj s 24-bajtovým kľúčom. Potom zdieľané kľúče, ktoré knižnica odmietne: nie striktné base64, prázdny kľúč alebo kľúč nesprávnej dĺžky.

**Ukazuje:** presné podpisované reťazce, prácu s kľúčom (`K1 K2 K1` pre 16-bajtový kľúč), `Exception\InvalidArgumentException`.

<<< @/../examples/signatures/run.php{php}

## Výstup {#output}

<<< @/../examples/signatures/expected-output.txt{text}

## Poznámky {#notes}

- Kľúč bez zarovnania `=` sa prijme (striktné dekódovanie base64 v PHP to dovoľuje) a dá ten istý kľúč.
- Prázdny kľúč a 8-bajtový kľúč (`MTIzNDU2Nzg=` je `12345678`) sa odmietnu: OpenSSL by krátky kľúč doplnil nulovými bajtmi, takže prázdny kľúč by podpisoval kľúčom zo samých núl.
- Algoritmus a shellovú funkciu, ktorá robí to isté, nájdete v [Podpisoch](../guide/signatures).
