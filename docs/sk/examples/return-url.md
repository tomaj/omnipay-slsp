# Návratová URL

Controller návratovej URL spustený proti návratom, ktoré e-shop v praxi vidí. `bankReturn()` hrá úlohu banky a podpisuje cez `openssl_encrypt()`; `handleReturn()` je kód, ktorý by ste dali do svojho controllera.

**Ukazuje:** `completePurchase()` s očakávaným `vs`, `amount` a `ss`, odovzdanie aktuálnej Symfony požiadavky bráne, každú kontrolu a čo na ňu odpovedať, idempotentné označenie „zaplatené“.

<<< @/../examples/return-url/run.php{php}

## Výstup {#output}

<<< @/../examples/return-url/expected-output.txt{text}

## Čo testuje každý prípad {#what-each-case-tests}

| Prípad | Prečo skončí takto |
|---|---|
| rejected by the bank (odmietnuté bankou) | pravý návrat s `result=NOK`: `isSuccessful()` je `false` |
| result changed to OK (result zmenený na OK) | `result` aj `real` sú podpísané: podpis už nesedí |
| return of a 1.00 payment (návrat platby 1.00) | pravý návrat `OK` tej istej objednávky, ale na `1.00`: `Amount mismatch` |
| return of another order (návrat inej objednávky) | pravý návrat `OK` pre `vs=2024000999`: `Variable symbol mismatch` |
| paid in another currency (zaplatené v inej mene) | pravý podpísaný návrat s `mena=CZK`: `Currency mismatch` |
| another specific symbol (iný špecifický symbol) | pravý návrat so `ss=0000000002`, objednávka však očakáva `0000000001` |
| return URL opened directly (návratová URL otvorená priamo) | žiadny `SIGN2` |
| paid (zaplatené) | pravý návrat `OK` pre túto objednávku a sumu |
| paid, page reloaded (zaplatené, stránka obnovená) | ten istý návrat znova: handler označí objednávku ako zaplatenú len raz |
