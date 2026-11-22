<?php

declare(strict_types=1);

namespace Omnipay\Tests\SporoPay;

use Http\Mock\Client as MockClient;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Http\Client;
use Omnipay\SporoPay\Gateway;
use Omnipay\SporoPay\Message\CompletePurchaseResponse;
use Omnipay\SporoPay\Message\PurchaseRequest;
use Omnipay\SporoPay\Message\PurchaseResponse;
use Omnipay\SporoPay\Sign\Des3Sign;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[BackupGlobals(true)]
final class GatewayTest extends TestCase
{
    private const SHARED_SECRET = 'Z3qY08EpvLlAAoMZdnyUdQ==';

    private Gateway $gateway;

    #[\Override]
    protected function setUp(): void
    {
        $this->gateway = new Gateway(new Client(new MockClient()), new Request());
        $this->gateway->setSharedSecret(self::SHARED_SECRET);
    }

    public function testName(): void
    {
        self::assertSame('SporoPay SLSP Gateway', $this->gateway->getName());
    }

    public function testPurchaseSign(): void
    {
        $request = $this->gateway->purchase($this->purchaseParameters());

        self::assertInstanceOf(PurchaseRequest::class, $request);
        self::assertSame('5.80', $request->getAmount());

        $response = $request->send();

        self::assertInstanceOf(PurchaseResponse::class, $response);
        self::assertTrue($response->isRedirect());
        self::assertFalse($response->isSuccessful());
        self::assertSame('GET', $response->getRedirectMethod());
        self::assertSame(
            'https://ib.slsp.sk/epayment/epayment/epayment.xml?pu_predcislo=000000&pu_cislo=0013662162&pu_kbanky=0900&suma=5.80&mena=EUR&vs=9960012037&ss=0000002313&url=http%3A%2F%2Flocalhost%3A4444%2Ftestserver.php&param=abc%3Ddefgh&sign1=K0%2BCFnMDIw09Hi8%2FK%2BDwp10Gv8BiVZID',
            $response->getRedirectUrl(),
        );
    }

    public function testPurchaseInTestModeUsesSimulator(): void
    {
        $this->gateway->setTestMode(true);
        $response = $this->gateway->purchase($this->purchaseParameters())->send();

        self::assertInstanceOf(PurchaseResponse::class, $response);
        self::assertStringStartsWith('https://platby.tomaj.sk/payment/sporopay-des?', $response->getRedirectUrl());
    }

    public function testPurchaseInTestModeUsesConfiguredTestHost(): void
    {
        $this->gateway->setTestMode(true);
        $this->gateway->setTestHost('http://localhost:8080');
        $response = $this->gateway->purchase($this->purchaseParameters())->send();

        self::assertInstanceOf(PurchaseResponse::class, $response);
        self::assertStringStartsWith('http://localhost:8080/payment/sporopay-des?', $response->getRedirectUrl());
    }

    public function testPurchaseRequiresParameters(): void
    {
        $this->expectException(InvalidRequestException::class);

        $this->gateway->purchase(['amount' => '5.80'])->send();
    }

    public function testCompletePurchaseWithValidSignature(): void
    {
        $_GET = $this->signedNotification('OK');

        $response = $this->gateway->completePurchase()->send();

        self::assertInstanceOf(CompletePurchaseResponse::class, $response);
        self::assertTrue($response->isSuccessful());
        self::assertSame('OK', $response->getRes());
        self::assertSame('9960012037', $response->getVs());
    }

    public function testCompletePurchaseWithFailedPayment(): void
    {
        $_GET = $this->signedNotification('NOK');

        $response = $this->gateway->completePurchase()->send();

        self::assertInstanceOf(CompletePurchaseResponse::class, $response);
        self::assertFalse($response->isSuccessful());
        self::assertSame('NOK', $response->getRes());
    }

    public function testCompletePurchaseWithInvalidSignature(): void
    {
        $_GET = $this->signedNotification('OK');
        $_GET['suma'] = '9.99';

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('incorect signature');

        $this->gateway->completePurchase()->send();
    }

    public function testCompletePurchaseWithoutSignature(): void
    {
        $_GET = ['vs' => '9960012037', 'result' => 'OK'];

        $this->expectException(InvalidRequestException::class);

        $this->gateway->completePurchase()->send();
    }

    /**
     * @return array<string, string>
     */
    private function purchaseParameters(): array
    {
        return [
            'amount' => '5.80',
            'vs' => '9960012037',
            'rurl' => 'http://localhost:4444/testserver.php',
            'cs' => '0321',
            'ss' => '0000002313',
            'account_number_prefix' => '000000',
            'account_number' => '0013662162',
            'param' => 'abc=defgh',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function signedNotification(string $result): array
    {
        $query = [
            'u_predcislo' => '000000',
            'u_cislo' => '1234567890',
            'u_kbanky' => '0900',
            'pu_predcislo' => '000000',
            'pu_cislo' => '0013662162',
            'pu_kbanky' => '0900',
            'suma' => '5.80',
            'mena' => 'EUR',
            'vs' => '9960012037',
            'ss' => '0000002313',
            'url' => 'http://localhost:4444/testserver.php',
            'param' => 'abc=defgh',
            'result' => $result,
            'real' => $result,
        ];
        $query['SIGN2'] = (new Des3Sign())->sign(implode(';', $query), self::SHARED_SECRET);

        return $query;
    }
}
