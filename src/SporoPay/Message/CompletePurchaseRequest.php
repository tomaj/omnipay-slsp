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

        $data = [];
        $getParams = ['u_predcislo', 'u_cislo', 'u_kbanky', 'pu_predcislo', 'pu_cislo', 'pu_kbanky', 'suma', 'mena', 'vs', 'ss', 'url', 'param', 'result', 'real'];
        foreach ($getParams as $getParam){
            if(!isset($_GET[$getParam])){
                throw new InvalidRequestException(sprintf('one of the input parameters is missing: %1$s', $getParam));
            }

            $data[$getParam] = $_GET[$getParam];
        }

        $dataString = implode(';', $data);
        $sign = new Des3Sign();

        if ($sign->sign($dataString, $sharedSecret) != $_GET['SIGN2']) {
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
