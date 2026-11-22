# Contributing

Contributions are welcome and will be credited.

This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating, you agree to uphold it.

Security vulnerabilities are reported in private. See [SECURITY.md](SECURITY.md). Do not open a public issue for a security report.

## Pull requests

- Follow [PER Coding Style](https://www.php-fig.org/per/coding-style/). `composer phpcs` checks `src`, `tests` and `examples`.
- Add tests for every behaviour change. A patch without tests will not be accepted. A change to a signed string needs a test vector computed independently, for example with the `openssl enc -des-ede3-cbc` command shown in the signatures documentation.
- Document behaviour changes in the documentation (`docs/`) and `CHANGELOG.md`.
- Follow [Semantic Versioning](https://semver.org/). The current line is 4.x. Breaking changes wait for the next major version.
- Open one pull request per change, from a feature branch. Do not ask us to pull from your `master` branch.
- Keep the history coherent. Squash work-in-progress commits before submitting.

## Development

omnipay-slsp supports PHP 8.4, 8.5 and 8.6. Continuous integration runs on PHP 8.4, PHP 8.4 with the lowest dependencies, PHP 8.5, and PHP 8.6, which also runs the 90% coverage check and Infection. A nightly PHP 8.7 build runs too, but its failure does not fail the workflow.

```bash
git clone https://github.com/tomaj/omnipay-slsp.git
cd omnipay-slsp
composer install
composer phpunit
composer phpstan
composer phpcs
composer rector
```

`composer check` runs code style, PHPStan, Rector and the tests in one go.

PHPUnit fails on any notice, deprecation or warning, including PHPUnit's own. Deprecations that one dependency raises inside another are ignored, so the lowest supported dependencies still pass.

`composer phpunit` sets `XDEBUG_MODE=off` and passes `--no-coverage`. `composer coverage` (HTML and Clover report in `build/`) and `composer infection` turn coverage on; they need Xdebug or PCOV.

The examples in `examples/` are included in the documentation and run by the test suite (`tests/ExamplesTest.php`), so keep them working.

The documentation site lives in `docs/` (VitePress):

```bash
cd docs
npm ci
npm run docs:dev    # local preview
npm run docs:links  # build the site and check every link
```

Maintainers publish releases by pushing a version tag; see [RELEASING.md](RELEASING.md).

By contributing, you agree that your contributions are licensed under the MIT License.
