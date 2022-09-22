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
        $getParams = ['u_predcislo', 'u_cislo', 'u_kbanky', 'pu_predcislo', 'pu_cislo', 'pu_kbanky', 'suma', 'mena', 'vs', 'ss', 'url', /*'param',*/ 'result', 'real'];
        foreach ($getParams as $getParam){
            if(!isset($_GET[$getParam])){
                throw new InvalidRequestException(sprintf('one of the input parameters is missing: %1$s', $getParam));
            }

            $data[$getParam] = $_GET[$getParam];
        }

        $dataString = implode(';', $data);
        $sign = new Des3Sign();

        if(!isset($_GET['SIGN2'])){
            throw new InvalidRequestException(sprintf('missing input parameter: SIGN2', $getParam));
        }

        if ($sign->sign($dataString, $sharedSecret) != $_GET['SIGN2']) {
            // Ještě to zkusím s prázdným parametrem 'param', který dřív posílali - ale musím ho vložit na správnou pozici - před 'result'.
            // Implode se dělá jen z hodnot, takže je vlastně jedno, jak pojmenuju ten vložený parametr. Ale nechám tam param, když tam dřív býval ...
            $pos = array_search('result', array_keys($data));
            $data2 = array_slice($data, 0, $pos, true) +  array('param' => '') +  array_slice($data, $pos, count($data)-$pos, true);
            $dataString2 = implode(';', $data2);

            if ($sign->sign($dataString2, $sharedSecret) != $_GET['SIGN2']) {
                throw new InvalidRequestException('incorect signature');
            }
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
