<?php

declare(strict_types=1);

namespace Omnipay\SporoPay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\SporoPay\Sign\Des3Sign;
use Omnipay\SporoPay\StringParameterTrait;

final class PurchaseRequest extends AbstractRequest
{
    use StringParameterTrait;

    /**
     * @return array<string, ?string>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('pu_predcislo', 'pu_cislo', 'vs', 'ss', 'rurl', 'param');
        $data = [];

        $data['pu_predcislo'] = $this->getAccountNumberPrefix();
        $data['pu_cislo'] = $this->getAccountNumber();
        $data['pu_kbanky'] = '0900';
        $data['suma'] = $this->getAmount();
        $data['mena'] = 'EUR';
        $data['vs'] = $this->getVs();
        $data['ss'] = $this->getSs();
        $data['url'] = $this->getRurl();
        $data['param'] = $this->getParam();

        return $data;
    }

    public function generateSignature(string $data): string
    {
        $sign = new Des3Sign();
        return $sign->sign($data, $this->getSharedSecret() ?? '');
    }

    #[\Override]
    public function send(): PurchaseResponse
    {
        return $this->sendData($this->getData());
    }

    #[\Override]
    public function sendData(mixed $data): PurchaseResponse
    {
        if (!is_array($data)) {
            throw new InvalidRequestException('Purchase data must be an array');
        }

        $input = implode(';', [
            $this->getAccountNumberPrefix(),
            $this->getAccountNumber(),
            $this->stringValue($data['pu_kbanky'] ?? null),
            $this->getAmount(),
            $this->stringValue($data['mena'] ?? null),
            $this->getVs(),
            $this->getSs(),
            $this->getRurl(),
            $this->getParam(),
        ]);
        $data['sign1'] = $this->generateSignature($input);
        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint(): string
    {
        if ($this->getTestMode()) {
            $host = $this->getTestHost();
            if ($host === null || $host === '') {
                $host = 'https://platby.tomaj.sk';
            }
            return $host . '/payment/sporopay-des';
        }

        return 'https://ib.slsp.sk/epayment/epayment/epayment.xml';
    }

    public function getSharedSecret(): ?string
    {
        return $this->getStringParameter('sharedSecret');
    }

    public function setSharedSecret(?string $value): static
    {
        $this->setParameter('sharedSecret', $value);
        return $this;
    }

    public function getTestHost(): ?string
    {
        return $this->getStringParameter('testHost');
    }

    public function setTestHost(?string $value): static
    {
        $this->setParameter('testHost', $value);
        return $this;
    }

    public function getVs(): ?string
    {
        return $this->getStringParameter('vs');
    }

    public function setVs(?string $value): static
    {
        $this->setParameter('vs', $value);
        return $this;
    }

    public function getSs(): ?string
    {
        return $this->getStringParameter('ss');
    }

    public function setSs(?string $value): static
    {
        $this->setParameter('ss', $value);
        return $this;
    }

    public function getRurl(): ?string
    {
        return $this->getStringParameter('rurl');
    }

    public function setRurl(?string $value): static
    {
        $this->setParameter('rurl', $value);
        return $this;
    }

    public function getAccountNumberPrefix(): ?string
    {
        return $this->getStringParameter('pu_predcislo');
    }

    public function setAccountNumberPrefix(?string $value): static
    {
        $this->setParameter('pu_predcislo', $value);
        return $this;
    }

    public function getAccountNumber(): ?string
    {
        return $this->getStringParameter('pu_cislo');
    }

    public function setAccountNumber(?string $value): static
    {
        $this->setParameter('pu_cislo', $value);
        return $this;
    }

    public function getParam(): ?string
    {
        return $this->getStringParameter('param');
    }

    public function setParam(?string $value): static
    {
        $this->setParameter('param', $value);
        return $this;
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
