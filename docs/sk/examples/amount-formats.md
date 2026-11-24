# Formáty sumy

SporoPay podpisuje sumu (`suma`) v oboch smeroch a `completePurchase()` porovná vrátenú `suma` so sumou vašej objednávky **ako text**, potom čo Omnipay vašu sumu naformátoval na dve desatinné miesta. Tento príklad ukazuje, čo to znamená pre hodnoty, ktoré e-shop zvyčajne ukladá.

**Ukazuje:** formátovanie sumy v `purchase()`, porovnanie v `completePurchase()`, `Amount precision is too high for currency.`, `A negative amount is not allowed.`, `The amount parameter is required`.

<<< @/../examples/amount-formats/run.php{php}

## Výstup {#output}

<<< @/../examples/amount-formats/expected-output.txt{text}

## Poznámky {#notes}

- Sumu ukladajte ako desatinný reťazec (`'10.50'`) alebo ako celé centy a formátujte ju sami; float funguje pre sumy ako `10.5`, ale float, ktorý sa nedá presne reprezentovať, sa môže naformátovať inak, než čakáte.
- `purchase()` vždy posiela dve desatinné miesta, takže aj pravý návrat má vždy dve desatinné miesta. `suma` s hodnotou `10.5` môže prísť len odinakiaľ a odmietne sa.
