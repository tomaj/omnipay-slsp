# Presmerovanie na platbu

Strana pokladne: zostavenie podpísanej požiadavky a pohľad na URL, na ktorú sa zákazník pošle.

**Ukazuje:** parametre `purchase()` (`accountNumberPrefix`, `accountNumber`, `amount`, `vs`, `ss`, `rurl`, `param`), pevné `pu_kbanky` a `mena`, metódy `PurchaseResponse`, `getRedirectResponse()`.

<<< @/../examples/purchase-redirect/run.php{php}

## Výstup {#output}

<<< @/../examples/purchase-redirect/expected-output.txt{text}

## Poznámky {#notes}

- `sign1` sa overuje voči príkazovému riadku `openssl` v príklade [Podpisy cez openssl](./signatures).
- V controlleri zavolajte `$response->redirect()` a skončite, alebo vo frameworku vráťte z akcie `$response->getRedirectResponse()`. Pozrite [Začatie platby](../guide/purchase).
