<?php

namespace Omnipay\SporoPay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\SporoPay\Sign\Des3Sign;

class CompletePurchaseRequest extends AbstractRequest
{
    public function getData()
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        $data = "{$_GET['u_predcislo']};{$_GET['u_cislo']};{$_GET['u_kbanky']};{$_GET['pu_predcislo']};{$_GET['pu_cislo']};{$_GET['pu_kbanky']};{$_GET['suma']};{$_GET['mena']};{$_GET['vs']};{$_GET['ss']};{$_GET['url']};{$_GET['param']};{$_GET['result']};{$_GET['real']}";
    	$sign = new Des3Sign();

        if ($sign->sign($data, $sharedSecret) != $_GET['SIGN2']) {
            throw new InvalidRequestException('incorect signature');
        }

        return [
            'RES' => $_GET['result'],
            'VS' => $_GET['vs'],
        ];
    }

    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }

    public function getSharedSecret()
    {
        return $this->getParameter('sharedSecret');
    }

    public function setSharedSecret($value)
    {
        return $this->setParameter('sharedSecret', $value);
    }
}