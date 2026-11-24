# Priebeh platby

SporoPay je platba s presmerovaním: váš server s bankou nikdy priamo nekomunikuje. Prehliadač zákazníka prenesie podpísanú požiadavku do Slovenskej sporiteľne a podpísanú odpoveď späť. Brána tú prvú zostaví a tú druhú overí.

<Diagram name="flow" :width="760" :height="512" alt="Sekvencia: zákazník zaplatí v e-shope; purchase() odpovie presmerovaním do SporoPay s podpísanými poľami v query stringu; zákazník zaplatí v internet bankingu; SporoPay presmeruje prehliadač na návratovú URL so všetkými poľami a SIGN2; completePurchase() overí podpis, variabilný symbol, sumu a menu" />

## Krok za krokom {#step-by-step}

1. **Pokladňa.** E-shop si uloží objednávku (sumu, variabilný symbol, špecifický symbol) a zavolá `purchase()`. Brána podpíše `pu_predcislo;pu_cislo;pu_kbanky;suma;mena;vs;ss;url;param` zdieľaným kľúčom ([Podpisy](./signatures)) do `sign1`.
2. **Presmerovanie.** `redirect()` odpovie presmerovaním 302 na `https://ib.slsp.sk/epayment/epayment/epayment.xml` (alebo na simulátor v [testovacom režime](./test-mode)), polia a `sign1` sú v query stringu. Z vášho servera do banky nejde nič.
3. **Platba.** SporoPay overí `sign1`, zákazník sa prihlási do internet bankingu a platbu potvrdí alebo odmietne.
4. **Návrat.** SporoPay presmeruje prehliadač na vašu `url` s účtom platiteľa (`u_predcislo`, `u_cislo`, `u_kbanky`), vaším účtom a poľami platby tak, ako ste ich poslali, s `result` (`OK` alebo `NOK`), `real` a `SIGN2`, podpisom cez všetky z nich.
5. **Overenie.** Návratová URL zavolá `completePurchase()` s variabilným symbolom a sumou (a špecifickým symbolom) objednávky. Brána znova vypočíta `SIGN2`, porovná ho v konštantnom čase a skontroluje variabilný symbol, sumu, menu a špecifický symbol voči objednávke. Potom vám `isSuccessful()` povie, či je `result` rovné `OK`.

## Čo vám návrat povie {#what-the-return-tells-you}

Všetky polia návratu pokrýva `SIGN2`:

| Pole | Význam |
|---|---|
| `u_predcislo`, `u_cislo`, `u_kbanky` | účet platiteľa: predčíslie, číslo, kód banky |
| `pu_predcislo`, `pu_cislo`, `pu_kbanky` | váš účet, tak ako ste ho poslali |
| `suma`, `mena` | suma a mena (`EUR`), tak ako ste ich poslali |
| `vs`, `ss` | variabilný a špecifický symbol, tak ako ste ich poslali |
| `url`, `param` | vaša návratová URL a váš voľný text, tak ako ste ich poslali |
| `result` | `OK` zaplatené, `NOK` nezaplatené |
| `real` | ďalšie stavové pole banky (`OK` alebo `NOK`), vracia ho `getReal()`; `isSuccessful()` používa len `result` |

Návrat nemá časovú pečiatku: ten istý návrat sa pri zopakovaní overí znova.

## Čo sa v praxi stáva {#things-that-happen-in-practice}

- **Zákazník po zaplatení zatvorí prehliadač.** SporoPay nemá žiadnu notifikáciu zo servera na server, takže objednávka zostane vo vašom e-shope nezaplatená, kým ju nespárujete s bankovým výpisom.
- **Zákazník obnoví návratovú stránku.** Rovnaký podpísaný návrat príde znova: označenie objednávky ako zaplatenej musí byť idempotentné ([príklad](../examples/return-url)).
- **Zákazník platbu v internet bankingu odmietne:** `result=NOK`. Nechajte ho zaplatiť znova.
- **Niekto upraví query string**, napríklad `suma` alebo `result`. Podpis potom nesedí a `completePurchase()` vyhodí výnimku ([Spracovanie chýb](./errors)).
- **Niekto zopakuje pravý návrat lacnejšej platby.** Podpis sedí, ale variabilný symbol alebo suma nie: `completePurchase()` vyhodí výnimku.

[Príklad celého priebehu s presmerovaním](../examples/full-flow) to všetko spustí na localhoste s falošnou bankou.
