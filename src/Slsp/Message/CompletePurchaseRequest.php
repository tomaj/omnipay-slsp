<?php

namespace Omnipay\Slsp\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Slsp\Sign\HmacSign;

class CompletePurchaseRequest extends AbstractRequest
{
    public function getData()
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        $data = "{$_GET['u_predcislo']};{$_GET['u_cislo']};{$_GET['u_kbanky']};{$_GET['pu_predcislo']};{$GET['pu_cislo']};{$_GET['pu_kbanky']};{$_GET['suma']};{$_GET['mena']};{$_GET['vs']};{$_GET['ss']};{$_GET['url']};{$_GET['param']};{$_GET['result']};{$_GET['real']}";
    	$sign = new HmacSign();

        if ($sign->sign($data, $sharedSecret) != $_GET['SIGN2']) {
            throw new InvalidRequestException('incorect signature');
        }

        return [
            'RES' => $_GET['RES'],
            'VS' => $_GET['VS'],
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