<?php

namespace Omnipay\SporoPay;

use Omnipay\Common\AbstractGateway;

class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'SporoPay SLSP Gateway';
    }

    public function getDefaultParameters()
    {
        return [
            'sharedSecret' => '',
            'testHost' => '',
        ];
    }

    public function getSharedSecret()
    {
        return $this->getParameter('sharedSecret');
    }
    
    public function setSharedSecret($value)
    {
        return $this->setParameter('sharedSecret', $value);
    }

    public function getTestHost()
    {
        return $this->getParameter('testHost');
    }

    public function setTestHost($value)
    {
        return $this->setParameter('testHost', $value);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\SporoPay\Message\PurchaseRequest::class, $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\SporoPay\Message\CompletePurchaseRequest::class, $parameters);
    }
}
