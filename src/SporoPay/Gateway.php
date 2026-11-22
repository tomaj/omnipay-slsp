<?php

declare(strict_types=1);

namespace Omnipay\SporoPay;

use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\SporoPay\Message\CompletePurchaseRequest;
use Omnipay\SporoPay\Message\PurchaseRequest;

class Gateway extends AbstractGateway
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

    /**
     * @param array<string, mixed> $parameters
     */
    public function purchase(array $parameters = []): RequestInterface
    {
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function completePurchase(array $parameters = []): RequestInterface
    {
        return $this->createRequest(CompletePurchaseRequest::class, $parameters);
    }
}
