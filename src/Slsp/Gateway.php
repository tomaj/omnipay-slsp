<?php

namespace Omnipay\Slsp;

use Omnipay\Common\AbstractGateway;

class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'Slsp Gateway';
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
        return $this->createRequest('\Omnipay\Slsp\Message\PurchaseRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\Slsp\Message\CompletePurchaseRequest', $parameters);
    }
}