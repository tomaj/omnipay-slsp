# Changelog

## Unreleased

### Changed (BC break)

- Requires PHP 8.3 or newer (no PHP constraint before; Travis tested PHP 5.6 and 7.0).
- Requires `omnipay/common` ^3.2 (was ~3.0).
- Parameter getters and setters are typed: getters return `?string`, setters take `?string` and return `static`;
  `Des3Sign::sign()` takes and returns `string`. Code that extends these classes must match the new signatures.
- `Des3Sign::sign()` throws `InvalidArgumentException` when the shared secret is not valid base64 (invalid characters
  were silently skipped before) and `RuntimeException` when OpenSSL fails (an empty signature was returned before).
- `CompletePurchaseRequest` compares the `SIGN2` query parameter with `hash_equals()` instead of `!=`;
  missing query parameters no longer emit warnings (they are treated as empty strings).
- The no-op `PurchaseRequest::initialize()` override is removed (the parent implementation is used).

### Added

- GitHub Actions CI (PHP 8.3 - 8.6, nightly 8.7, lowest dependencies), PHPUnit 12, PHP-CS-Fixer (PER-CS),
  PHPStan level max with strict rules, `composer audit`, Dependabot.
- Tests for the test-mode endpoints and the complete purchase signature check.

### Removed

- Travis CI configuration, `omnipay/tests` and `squizlabs/php_codesniffer` dev dependencies.
