# Examples

Every example is a small Composer project in [`examples/`](https://github.com/tomaj/omnipay-slsp/tree/master/examples) with the code shown on its page and an `expected-output.txt`. CI installs each one against the code of the pull request and fails when the output changes, so what you read here is what the library does. They run offline: the bank's side is played by a few lines of PHP that sign with `openssl_encrypt()` (and the `openssl` command line), independently of the library.

| Example | What it shows | Runs |
|---|---|---|
| [Payment redirect](./purchase-redirect) | `purchase()`, every field of the signed URL, the constants, `getRedirectResponse()` | `php run.php` |
| [Return URL](./return-url) | `completePurchase()` in a controller: paid, rejected, edited result, smaller amount, another order, another currency, another specific symbol, no parameters, reload | `php run.php` |
| [Amount formats](./amount-formats) | how amounts are formatted in `purchase()` and compared in `completePurchase()` | `php run.php` |
| [Signatures with openssl](./signatures) | `sign1` and `SIGN2` from the library and from the `openssl` CLI, 16- and 24-byte keys, invalid secrets | `php run.php` |
| [Full redirect flow](./full-flow) | a shop and a fake bank on `php -S`, a script as the browser: pay, reject, edited amount, forged return | `bash e2e.sh` |

## Run one

```bash
git clone https://github.com/tomaj/omnipay-slsp.git
cd omnipay-slsp/examples/return-url
composer install
php run.php            # or: bash e2e.sh in examples/full-flow
```

`examples/ci/run.sh <name>` does what CI does: copies the example to a temporary directory, installs it against your checkout (a Composer path repository) and compares the output with `expected-output.txt`.

The examples use the payment simulator's shared secret (`Z3qY08EpvLlAAoMZdnyUdQ==`) and made-up accounts. In your shop, load the secret from the environment.
