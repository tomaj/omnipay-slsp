<?php

declare(strict_types=1);

namespace Omnipay\SporoPay;

/**
 * Reads a parameter that the gateway handles as a string (ids, symbols, urls, secrets).
 */
trait StringParameterTrait
{
    protected function getStringParameter(string $key): ?string
    {
        $value = $this->getParameter($key);
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value)) {
            return (string) $value;
        }

        return null;
    }
}
