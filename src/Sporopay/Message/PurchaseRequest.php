<?php


namespace Omnipay\SporoPay\Message;

use Omnipay\Common\Currency;
use Omnipay\Sporopay\Sign\Des3Sign;
use Omnipay\Common\Message\AbstractRequest;

class PurchaseRequest extends AbstractRequest
{
    public function initialize(array $parameters = array())
    {
        parent::initialize($parameters);
        return $this;
    }

    public function getData()
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

    public function generateSignature($data)
    {
        $sign = new Des3Sign();
        return $sign->sign($data, $this->getParameter('sharedSecret'));
    }

    public function sendData($data)
    {
        $input = "{$this->getAccountNumberPrefix()};{$this->getAccountNumber()};{$data['pu_kbanky']};{$this->getAmount()};{$data['mena']};{$this->getVs()};{$this->getSs()};{$this->getRurl()};{$this->getParam()}";
        $data['sign1'] = $this->generateSignature($input);
        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint()
    {
        if ($this->getTestmode()) {
            return 'https://platby.tomaj.sk/payment/slsp3des';
        } else {
            return 'https://ib.slsp.sk/epayment/epayment/epayment.xml';
        }
    }

    public function getSharedSecret()
    {
        return $this->getParameter('sharedSecret');
    }

    public function setSharedSecret($value)
    {
        return $this->setParameter('sharedSecret', $value);
    }

    public function getVs()
    {
        return $this->getParameter('vs');
    }

    public function setVs($value)
    {
        return $this->setParameter('vs', $value);
    }

    public function getSs()
    {
        return $this->getParameter('ss');
    }

    public function setSs($value)
    {
        return $this->setParameter('ss', $value);
    }

    public function getRurl()
    {
        return $this->getParameter('rurl');
    }
    
    public function setRurl($value)
    {
        return $this->setParameter('rurl', $value);
    }

    public function getAccountNumberPrefix()
    {
        return $this->getParameter('pu_predcislo');
    }
    
    public function setAccountNumberPrefix($value)
    {
        return $this->setParameter('pu_predcislo', $value);
    }

    public function getAccountNumber()
    {
        return $this->getParameter('pu_cislo');
    }
    
    public function setAccountNumber($value)
    {
        return $this->setParameter('pu_cislo', $value);
    }

    public function getParam()
    {
        return $this->getParameter('param');
    }
    
    public function setParam($value)
    {
        return $this->setParameter('param', $value);
    }
}