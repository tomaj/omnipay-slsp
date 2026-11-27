<?php

declare(strict_types=1);

namespace Omnipay\SporoPay\Message;

use Omnipay\Common\Exception\InvalidRequestException;

/**
 * Format checks of the purchase parameters, run before anything is signed.
 *
 * @internal
 */
final readonly class ParameterGuard
{
    /**
     * Variable and specific symbol: digits only, 1 to 10 (the Slovak payment symbol format).
     */
    private const string SYMBOL_PATTERN = '/\A[0-9]{1,10}\z/';

    /**
     * Account number prefix (the first part of a Slovak BBAN): digits only, 1 to 6.
     */
    private const string ACCOUNT_PREFIX_PATTERN = '/\A[0-9]{1,6}\z/';

    /**
     * Account number (the second part of a Slovak BBAN): digits only, 1 to 10.
     */
    private const string ACCOUNT_NUMBER_PATTERN = '/\A[0-9]{1,10}\z/';

    /**
     * @throws InvalidRequestException when the value is not 1 to 10 digits; the message names the parameter
     */
    public static function symbol(string $name, string $value): void
    {
        if (preg_match(self::SYMBOL_PATTERN, $value) !== 1) {
            throw new InvalidRequestException("The {$name} parameter must be up to 10 digits");
        }
    }

    /**
     * @throws InvalidRequestException when the account number prefix or the account number has an invalid format
     */
    public static function account(string $prefix, string $number): void
    {
        if (preg_match(self::ACCOUNT_PREFIX_PATTERN, $prefix) !== 1) {
            throw new InvalidRequestException('The pu_predcislo parameter must be up to 6 digits');
        }
        if (preg_match(self::ACCOUNT_NUMBER_PATTERN, $number) !== 1) {
            throw new InvalidRequestException('The pu_cislo parameter must be up to 10 digits');
        }
    }

    /**
     * The return URL must be absolute, so the bank can send the customer back, and use HTTPS
     * (plain HTTP only in test mode, for a shop running locally).
     *
     * @throws InvalidRequestException when the URL is not absolute, has another scheme, or contains whitespace,
     *                                 control characters or ';' (the separator of the signed fields)
     */
    public static function returnUrl(string $rurl, bool $testMode): void
    {
        $message = $testMode
            ? 'The rurl parameter must be an absolute https:// or http:// URL'
            : 'The rurl parameter must be an absolute https:// URL';
        if (preg_match('/[\x00-\x20;\x7F]/', $rurl) === 1) {
            throw new InvalidRequestException($message);
        }
        $scheme = parse_url($rurl, PHP_URL_SCHEME);
        $host = parse_url($rurl, PHP_URL_HOST);
        if (!is_string($scheme) || !is_string($host) || $host === '') {
            throw new InvalidRequestException($message);
        }
        $scheme = strtolower($scheme);
        if ($scheme !== 'https' && ($scheme !== 'http' || !$testMode)) {
            throw new InvalidRequestException($message);
        }
    }

    /**
     * `param` is free text that the bank returns signed. Control characters (line breaks, NUL, ...) would
     * end up in the signed strings and in the return URL, and ';' separates the signed fields, so a value
     * containing it could be split differently (completePurchase() rejects such a return).
     *
     * @throws InvalidRequestException when the value contains a control character or ';'
     */
    public static function param(string $param): void
    {
        if (preg_match('/[\x00-\x1F;\x7F]/', $param) === 1) {
            throw new InvalidRequestException('The param parameter must not contain control characters or ";"');
        }
    }
}
