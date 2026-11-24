# Contributing

Pull requests are welcome on [tomaj/omnipay-slsp](https://github.com/tomaj/omnipay-slsp). [CONTRIBUTING.md](https://github.com/tomaj/omnipay-slsp/blob/master/CONTRIBUTING.md) is the project policy; participation follows the [code of conduct](https://github.com/tomaj/omnipay-slsp/blob/master/CODE_OF_CONDUCT.md). Report security issues privately, see [SECURITY.md](https://github.com/tomaj/omnipay-slsp/blob/master/SECURITY.md).

## Changes we can merge

- [PER Coding Style](https://www.php-fig.org/per/coding-style/), checked by `composer phpcs` in `src`, `tests` and `examples`.
- Tests for every behaviour change. A change to a signed string needs a test vector computed independently with the openssl CLI, see [Signatures](./guide/signatures#verify-with-openssl).
- Behaviour changes documented in `CHANGELOG.md` and on these pages.
- [Semantic Versioning](https://semver.org/): the current line is 4.x; breaking changes wait for the next major version.

## Checks

```bash
composer install
composer check      # code style, PHPStan, Rector, tests
composer coverage   # HTML and Clover report in build/ (Xdebug or PCOV)
composer infection  # mutation testing
```

CI runs PHP 8.4 (also with the lowest dependencies), 8.5 and 8.6 (coverage of at least 90 % and Infection), and a nightly PHP 8.7 that may fail.

## Documentation

These pages are in `docs/` (VitePress). The longer code samples are imported from [`examples/`](https://github.com/tomaj/omnipay-slsp/tree/master/examples): each example is a Composer project with an `expected-output.txt`, run by the `Examples` workflow against the pull request's code and by `tests/SporoPay/ExamplesTest.php` on every supported PHP. Every other ```` ```php ```` block in the README, `UPGRADE-4.0.md` and `docs/` runs in `tests/SporoPay/DocsSnippetsTest.php`; a ```` ```text ```` block after `<!-- snippet: output -->` is its expected output, and `<!-- snippet: skip -->` before a block skips it (only for code that cannot run on its own, such as the 3.x side of an upgrade step).

```bash
cd docs
npm ci
npm run docs:dev    # preview on http://localhost:5173/omnipay-slsp/
npm run docs:links  # build and check every link
npm run images      # re-render og.png and the PNG icons after changing the SVGs (needs Chrome)
python3 scripts/check-svg.py
```

Run one example like CI does: `examples/ci/run.sh return-url`.
