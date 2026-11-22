<?php

declare(strict_types=1);

namespace Omnipay\SporoPay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    #[\Override]
    public function isSuccessful(): bool
    {
        return false;
    }

    #[\Override]
    public function isRedirect(): bool
    {
        return true;
    }

    #[\Override]
    public function getRedirectUrl(): string
    {
        $request = $this->getRequest();
        if (!$request instanceof PurchaseRequest) {
            throw new \LogicException('PurchaseResponse requires a PurchaseRequest');
        }

        return $request->getEndpoint() . '?' . http_build_query($this->getRedirectData());
    }

    #[\Override]
    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    /**
     * @return array<mixed>
     */
    #[\Override]
    public function getRedirectData(): array
    {
        return is_array($this->data) ? $this->data : [];
    }
}
