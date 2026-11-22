<?php

declare(strict_types=1);

namespace Omnipay\SporoPay\Message;

use Omnipay\Common\Message\AbstractResponse;

final class CompletePurchaseResponse extends AbstractResponse
{
    public const string SUCCESS = 'OK';

    #[\Override]
    public function isSuccessful(): bool
    {
        return self::SUCCESS === $this->getRes();
    }

    public function getRes(): ?string
    {
        return $this->dataString('RES');
    }

    public function getVs(): ?string
    {
        return $this->dataString('VS');
    }

    /**
     * The specific symbol returned (and signed) by the bank.
     */
    public function getSs(): ?string
    {
        return $this->dataString('SS');
    }

    /**
     * The amount (suma) returned (and signed) by the bank, for example '10.00'.
     */
    public function getAmount(): ?string
    {
        return $this->dataString('AMOUNT');
    }

    /**
     * The currency (mena) returned (and signed) by the bank: 'EUR'.
     */
    public function getCurrency(): ?string
    {
        return $this->dataString('CURRENCY');
    }

    /**
     * The signed "real" field of the return, as sent by the bank (OK or NOK).
     */
    public function getReal(): ?string
    {
        return $this->dataString('REAL');
    }

    private function dataString(string $key): ?string
    {
        $value = is_array($this->data) ? $this->data[$key] ?? null : null;

        return is_string($value) ? $value : null;
    }
}
