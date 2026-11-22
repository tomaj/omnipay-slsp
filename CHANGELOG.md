# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased][unreleased]

### Added

* `LICENSE.md` (MIT, as declared in `composer.json`), `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md`, `RELEASING.md`, issue and pull request templates; `composer.json` has `support` links and suggests an HTTP client for Omnipay ([#7](https://github.com/tomaj/omnipay-slsp/issues/7)).
* Tests for every public accessor, the package exceptions, `PurchaseResponse` without a `PurchaseRequest`, non-string response data; PHPUnit fails on its own deprecations, notices, warnings and an empty suite: 98 % statement coverage (CI requires 90 %), Infection MSI 100 % (CI requires `minMsi` and `minCoveredMsi` 95) ([#12](https://github.com/tomaj/omnipay-slsp/issues/12)).
* Rector, PHPStan deprecation rules and Infection in `require-dev`; composer scripts `phpunit`, `coverage`, `infection`, `rector`, `rector-fix` with descriptions; `check` also runs Rector ([#9](https://github.com/tomaj/omnipay-slsp/issues/9)).
* GitHub Actions CI (tests on PHP 8.4 - 8.6 with lowest dependencies on 8.4, nightly 8.7, 90 % statement coverage, Infection, Rector), PHPUnit 13, PHP-CS-Fixer (PER-CS), PHPStan level max with strict rules, `composer audit`, Dependabot ([#6](https://github.com/tomaj/omnipay-slsp/pull/6), [#8](https://github.com/tomaj/omnipay-slsp/issues/8)).
* Tag-driven release workflow (GitHub release with CHANGELOG notes, source archives and build provenance), CodeQL and OpenSSF Scorecard workflows ([#8](https://github.com/tomaj/omnipay-slsp/issues/8)).
* Tests for the test-mode endpoints and the complete purchase signature check; `sign1`/`SIGN2` vectors computed independently with the `openssl` CLI (DES-EDE3-CBC), tampered notifications, invalid secrets ([#6](https://github.com/tomaj/omnipay-slsp/pull/6)).

### Changed

* **BREAKING CHANGE**: Requires PHP 8.4 or newer (no PHP constraint before; Travis tested PHP 5.6 and 7.0) ([#6](https://github.com/tomaj/omnipay-slsp/pull/6), [#10](https://github.com/tomaj/omnipay-slsp/issues/10)).
* **BREAKING CHANGE**: Requires `omnipay/common` ^3.2 (was ~3.0) ([#6](https://github.com/tomaj/omnipay-slsp/pull/6)).
* **BREAKING CHANGE**: Parameter getters and setters are typed: getters return `?string`, setters take `?string` and return `static`; `Des3Sign::sign()` takes and returns `string`. Code that extends these classes must match the new signatures ([#6](https://github.com/tomaj/omnipay-slsp/pull/6)).
* **BREAKING CHANGE**: `Des3Sign::sign()` decodes the shared secret with strict base64 and throws an `InvalidArgumentException` when it is not valid base64. Whitespace, line breaks and missing `=` padding are still accepted; any other character outside the base64 alphabet (URL-safe `-`/`_`, surrounding quotes from a config file, a malformed `=` padding) used to be silently skipped and is now rejected - check your configured secret before upgrading. It throws `RuntimeException` when OpenSSL fails (an empty signature was returned before) ([#6](https://github.com/tomaj/omnipay-slsp/pull/6)).
* **BREAKING CHANGE**: Setters are called with the declared `?string` type: passing an `int` (e.g. `setVs(123)`) from a file with `declare(strict_types=1)` now throws a `TypeError`; values passed through `initialize()`/`purchase([...])` are still coerced. An `int` `vs`/`ss` is now sent as a string (`"123"`); the URL and `sign1` are unchanged ([#6](https://github.com/tomaj/omnipay-slsp/pull/6)).
* **BREAKING CHANGE**: `Gateway`, `PurchaseRequest`, `PurchaseResponse`, `CompletePurchaseRequest`, `CompletePurchaseResponse` and `Des3Sign` are `final` (`Des3Sign` also `readonly`); compose them instead of extending. See [UPGRADE-4.0.md](https://github.com/tomaj/omnipay-slsp/blob/master/UPGRADE-4.0.md) ([#11](https://github.com/tomaj/omnipay-slsp/issues/11)).
* **BREAKING CHANGE**: `CompletePurchaseRequest` reads the bank's return parameters from the HTTP request passed to the gateway (Omnipay's default is `Request::createFromGlobals()`) instead of `$_GET`; frameworks and long-running servers must pass the current request to the gateway ([#11](https://github.com/tomaj/omnipay-slsp/issues/11)).
* **BREAKING CHANGE**: The package throws its own `Omnipay\SporoPay\Exception\InvalidArgumentException`, `RuntimeException` and `LogicException` (extending the SPL exceptions they replace) that implement the new package interface `Omnipay\SporoPay\Exception\SporoPayException`, which extends Omnipay's `OmnipayException` ([#11](https://github.com/tomaj/omnipay-slsp/issues/11)).
* `Gateway::purchase()`/`completePurchase()` return `PurchaseRequest`/`CompletePurchaseRequest`, and the requests' `send()` returns `PurchaseResponse`/`CompletePurchaseResponse` (narrower return types, no change for callers) ([#11](https://github.com/tomaj/omnipay-slsp/issues/11)).
* `CompletePurchaseRequest`: missing (or non-string) query parameters no longer emit "undefined array key" warnings; they are treated as empty strings, so a missing `SIGN2` fails the check and a missing `vs`/`result` makes `getVs()`/`getRes()` return `''` instead of `null` ([#6](https://github.com/tomaj/omnipay-slsp/pull/6)).

### Removed

* The Composer dist archive contains only the library (`src/`, `composer.json`, README, LICENSE, CHANGELOG, UPGRADE): tests, CI and tool configs are `export-ignore`d. The ad-hoc scripts `test.php` and `testserver.php` are removed; the documentation has working examples ([#7](https://github.com/tomaj/omnipay-slsp/issues/7)).
* The no-op `PurchaseRequest::initialize()` override (the parent implementation is used) ([#6](https://github.com/tomaj/omnipay-slsp/pull/6)).
* Travis CI configuration, the unused `omnipay/tests` and `squizlabs/php_codesniffer` dev dependencies ([#6](https://github.com/tomaj/omnipay-slsp/pull/6)).

### Security

* **Security**: **BREAKING CHANGE**: `completePurchase()` requires the `vs` and `amount` of the order being completed and, after the `SIGN2` check, throws `InvalidRequestException` when the signed return belongs to another payment: `Variable symbol mismatch`, `Amount mismatch` (the signed `suma` is compared with the amount formatted like `purchase()` sends it, e.g. `'5.80'`), `Currency mismatch` (`mena` is not `EUR`) and, when `ss` is passed, `Specific symbol mismatch`. Without `vs`/`amount` it throws `The vs parameter is required` / `The amount parameter is required`. `CompletePurchaseResponse` exposes the signed `getSs()`, `getAmount()`, `getCurrency()` and `getReal()`. See [UPGRADE-4.0.md](https://github.com/tomaj/omnipay-slsp/blob/master/UPGRADE-4.0.md) ([#15](https://github.com/tomaj/omnipay-slsp/issues/15)).
* **Security**: `CompletePurchaseRequest` compares the `SIGN2` query parameter with `hash_equals()` instead of `!=` ([#6](https://github.com/tomaj/omnipay-slsp/pull/6)).

## [3.2.0] - 2022-08-25

### Added

* `testHost` gateway parameter: the host of the test-mode endpoint (default `https://platby.tomaj.sk`), so test mode can point to your own simulator ([#5](https://github.com/tomaj/omnipay-slsp/pull/5)).

## [3.1.0] - 2020-01-24

### Changed

* The 3DES signature is computed with OpenSSL (`DES-EDE3-CBC`) instead of the removed `mcrypt` extension, so the package works on PHP 7.2 and newer. `ext-openssl` is required ([#4](https://github.com/tomaj/omnipay-slsp/pull/4)).

## [3.0.0] - 2019-03-08

### Changed

* **BREAKING CHANGE**: Requires `omnipay/common` 3 (was 2). Follow the Omnipay 3 upgrade guide (HTTP client, `Omnipay\Common\Http\ClientInterface`) ([#2](https://github.com/tomaj/omnipay-slsp/pull/2)).
* **BREAKING CHANGE**: PHP 5.5 is no longer supported (tested on PHP 5.6 and 7.0; 7.1+ needs 3.1.0 because the signature still used `mcrypt`).

### Fixed

* Test-mode endpoint points to the current simulator path (`/payment/sporopay-des`).

## [0.1.0] - 2017-01-09

Initial release. Omnipay 2 gateway `SporoPay` for SporoPay (Slovenská sporiteľňa, Slovakia):

* `purchase()` redirects the customer to SporoPay with the payment signed by 3DES (`sign1`).
* `completePurchase()` verifies the `SIGN2` of the bank's return request and exposes the variable symbol (`getVs()`) and result (`getRes()`).
* Test mode sends payments to a test server instead of Slovenská sporiteľňa.

[unreleased]: https://github.com/tomaj/omnipay-slsp/compare/3.2.0...HEAD
[3.2.0]: https://github.com/tomaj/omnipay-slsp/compare/3.1.0...3.2.0
[3.1.0]: https://github.com/tomaj/omnipay-slsp/compare/3.0.0...3.1.0
[3.0.0]: https://github.com/tomaj/omnipay-slsp/compare/0.1.0...3.0.0
[0.1.0]: https://github.com/tomaj/omnipay-slsp/releases/tag/0.1.0
