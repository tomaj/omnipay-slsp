<?php

namespace Omnipay\SporoPay;

use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    /**
     * @var Gateway
     */
    protected $gateway;

    public function setUp()
    {
        parent::setUp();
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
    }

    public function testPurchaseSign()
    {
        $this->gateway->setSharedSecret('Z3qY08EpvLlAAoMZdnyUdQ==');

        $request = $this->gateway->purchase(array(
        	'amount' => '5.80',
        	'vs' => '9960012037',
        	'rurl' => 'http://localhost:4444/testserver.php',
            'cs' => '0321',
            'ss' => '0000002313',
            'account_number_prefix' => '000000',
            'account_number' => '0013662162',
            'param' => 'abc=defgh',
        ));

        $this->assertInstanceOf('Omnipay\SporoPay\Message\PurchaseRequest', $request);
        $this->assertSame('5.80', $request->getAmount());

        $response = $request->send();

        $this->assertTrue($response->isRedirect());

        $this->assertEquals(
        	'https://ib.slsp.sk/epayment/epayment/epayment.xml?pu_predcislo=000000&pu_cislo=0013662162&pu_kbanky=0900&suma=5.80&mena=EUR&vs=9960012037&ss=0000002313&url=http%3A%2F%2Flocalhost%3A4444%2Ftestserver.php&param=abc%3Ddefgh&sign1=K0%2BCFnMDIw09Hi8%2FK%2BDwp10Gv8BiVZID',
        	$response->getRedirectUrl()
        );
    }
}