<?php

use Omnipay\Omnipay;

require_once __DIR__ . '/vendor/autoload.php';


$gateway = Omnipay::create('SporoPay');

$gateway->setSharedSecret('111111111111111111111111');

$gateway->setTestMode(true);

$response = $gateway->completePurchase([
	'amount' => '5.80',
    'VS' => '9960012037',
    'CS' => '0321',
    'ss' => '0000002313',
    'rurl' => 'http://localhost:4444/testserver.php',
    'account_number_prefix' => '000000',
    'account_number' => '0013662162',
    'param' => 'abc=defgh',
])->send();


if ($response->isSuccessful()) {
    
    // Payment was successful
    echo "OK - {$response->getVs()}";

} elseif ($response->isRedirect()) {
    
    // Redirect to offsite payment gateway
    echo($response->getRedirectUrl() . "\n");
    //$response->redirect();

} else {
	echo "FAIL!";
    // Payment failed
    echo $response->getMessage();
}


