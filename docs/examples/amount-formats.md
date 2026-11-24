# Amount formats

SporoPay signs the amount (`suma`) in both directions, and `completePurchase()` compares the returned `suma` with the amount of your order **as text**, after Omnipay formatted your amount with two decimals. This example shows what that means for the values a shop typically stores.

**Shows:** amount formatting in `purchase()`, the comparison in `completePurchase()`, `Amount precision is too high for currency.`, `A negative amount is not allowed.`, `The amount parameter is required`.

<<< @/../examples/amount-formats/run.php{php}

## Output

<<< @/../examples/amount-formats/expected-output.txt{text}

## Notes

- Store the amount as a decimal string (`'10.50'`) or integer cents and format it yourself; floats work for amounts like `10.5`, but a float that is not exactly representable may format differently than you expect.
- `purchase()` always sends two decimals, so a genuine return always has two decimals too. A `suma` of `10.5` can only come from somewhere else, and it is rejected.
