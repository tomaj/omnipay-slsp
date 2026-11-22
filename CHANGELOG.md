# Changelog

## Unreleased

### Changed (BC break)

- Requires PHP 8.3 or newer (no PHP constraint before; Travis tested PHP 5.6 and 7.0).
- Requires `omnipay/common` ^3.2 (was ~3.0).
- Parameter getters and setters are typed: getters return `?string`, setters take `?string` and return `static`;
  `Des3Sign::sign()` takes and returns `string`. Code that extends these classes must match the new signatures.
- `Des3Sign::sign()` decodes the shared secret with strict base64 and throws `InvalidArgumentException` when it is
  not valid base64. Whitespace, line breaks and missing `=` padding are still accepted; any other character outside
  the base64 alphabet (URL-safe `-`/`_`, surrounding quotes from a config file, a malformed `=` padding) used to be
  silently skipped and is now rejected - check your configured secret before upgrading.
  It throws `RuntimeException` when OpenSSL fails (an empty signature was returned before).
- `CompletePurchaseRequest` compares the `SIGN2` query parameter with `hash_equals()` instead of `!=`.
  Missing (or non-string) query parameters no longer emit "undefined array key" warnings; they are treated as
  empty strings, so a missing `SIGN2` fails the check and a missing `vs`/`result` makes `getVs()`/`getRes()`
  return `''` instead of `null`.
- Setters are called with the declared `?string` type: passing an `int` (e.g. `setVs(123)`) from a file with
  `declare(strict_types=1)` now throws a `TypeError`; values passed through `initialize()`/`purchase([...])`
  are still coerced. An `int` `vs`/`ss` is now sent as a string (`"123"`); the URL and `sign1` are unchanged.
- The no-op `PurchaseRequest::initialize()` override is removed (the parent implementation is used).

### Added

- GitHub Actions CI (PHP 8.3 - 8.6, nightly 8.7, lowest dependencies), PHPUnit 12, PHP-CS-Fixer (PER-CS),
  PHPStan level max with strict rules, `composer audit`, Dependabot.
- Tests for the test-mode endpoints and the complete purchase signature check; `sign1`/`SIGN2` vectors computed
  independently with the `openssl` CLI (DES-EDE3-CBC), tampered notifications, invalid secrets.

### Removed

- Travis CI configuration, the unused `omnipay/tests` and `squizlabs/php_codesniffer` dev dependencies.
