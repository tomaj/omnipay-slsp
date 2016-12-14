<?php

use Omnipay\Omnipay;

require_once __DIR__ . '/vendor/autoload.php';

$gateway = Omnipay::create('Slsp');


$gateway->setSharedSecret('1111111111111111');
// $gateway->setTestMode(true);


$response = $gateway->purchase([
	'amount' => '10.00',
	'VS' => '123456',
	'CS' => '0321',
    'ss' => '1111111111',
	'rurl' => 'http://localhost:4444/testserver.php',
    'account_number_prefix' => '111111',
    'account_number' => '1111111111'
])->send();

if ($response->isSuccessful()) {
    
    // Payment was successful
    print_r($response);

} elseif ($response->isRedirect()) {
    
    // Redirect to offsite payment gateway
    echo($response->getRedirectUrl() . "\n");

    // $response->redirect();

} else {

    // Payment failed
    echo $response->getMessage();
}


