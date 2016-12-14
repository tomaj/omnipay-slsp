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

    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SporoPay\Message\PurchaseRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\SporoPay\Message\CompletePurchaseRequest', $parameters);
    }
}