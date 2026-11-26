<?php

declare(strict_types=1);

namespace Omnipay\SporoPay;

use Omnipay\Common\AbstractGateway;
use Omnipay\SporoPay\Message\CompletePurchaseRequest;
use Omnipay\SporoPay\Message\PurchaseRequest;

final class Gateway extends AbstractGateway
{
    use StringParameterTrait;

    #[\Override]
    public function getName(): string
    {
        return 'SporoPay SLSP Gateway';
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function getDefaultParameters(): array
    {
        return [
            'sharedSecret' => '',
            'testHost' => '',
        ];
    }

    public function getSharedSecret(): ?string
    {
        return $this->getStringParameter('sharedSecret');
    }

    public function setSharedSecret(#[\SensitiveParameter] ?string $value): static
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

    /**
     * @param array<string, mixed> $parameters
     */
    public function purchase(array $parameters = []): PurchaseRequest
    {
        $request = new PurchaseRequest($this->httpClient, $this->httpRequest);
        $request->initialize(array_replace($this->getParameters(), $parameters));

        return $request;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function completePurchase(array $parameters = []): CompletePurchaseRequest
    {
        $request = new CompletePurchaseRequest($this->httpClient, $this->httpRequest);
        $request->initialize(array_replace($this->getParameters(), $parameters));

        return $request;
    }
}
