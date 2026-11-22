<?php

declare(strict_types=1);

namespace Omnipay\Tests\SporoPay;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\OmnipayException;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\SporoPay\Exception\InvalidArgumentException;
use Omnipay\SporoPay\Exception\LogicException;
use Omnipay\SporoPay\Exception\RuntimeException;
use Omnipay\SporoPay\Exception\SporoPayException;
use Omnipay\SporoPay\Gateway;
use Omnipay\SporoPay\Message\CompletePurchaseRequest;
use Omnipay\SporoPay\Message\CompletePurchaseResponse;
use Omnipay\SporoPay\Message\PurchaseRequest;
use Omnipay\SporoPay\Message\PurchaseResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class MessageTest extends TestCase
{
    public function testGatewayDefaultsAndAccessors(): void
    {
        $gateway = new Gateway(self::createStub(ClientInterface::class), new Request());

        self::assertSame('SporoPay', $gateway->getShortName());
        self::assertSame(['sharedSecret' => '', 'testHost' => ''], $gateway->getDefaultParameters());

        $gateway->initialize();
        self::assertSame('', $gateway->getSharedSecret());
        self::assertSame('', $gateway->getTestHost());
        self::assertNotTrue($gateway->getTestMode());

        $gateway->setSharedSecret('secret')->setTestHost('http://localhost');
        self::assertSame('secret', $gateway->getSharedSecret());
        self::assertSame('http://localhost', $gateway->getTestHost());
        self::assertSame('secret', $gateway->purchase()->getSharedSecret());
        self::assertSame('http://localhost', $gateway->purchase()->getTestHost());
        self::assertSame('secret', $gateway->completePurchase()->getSharedSecret());
        self::assertSame('other', $gateway->completePurchase(['sharedSecret' => 'other'])->getSharedSecret());
    }

    public function testPurchaseRequestAccessors(): void
    {
        $request = new PurchaseRequest(self::createStub(ClientInterface::class), new Request());
        $request->setSharedSecret('s')->setTestHost('h')->setVs('v')->setSs('ss')->setRurl('u')
            ->setAccountNumberPrefix('p')->setAccountNumber('n')->setParam('x');

        self::assertSame('s', $request->getSharedSecret());
        self::assertSame('h', $request->getTestHost());
        self::assertSame('v', $request->getVs());
        self::assertSame('ss', $request->getSs());
        self::assertSame('u', $request->getRurl());
        self::assertSame('p', $request->getAccountNumberPrefix());
        self::assertSame('n', $request->getAccountNumber());
        self::assertSame('x', $request->getParam());

        $request->initialize(['vs' => 123]);
        self::assertSame('123', $request->getVs());
        self::assertNull($request->getSs());
    }

    public function testGenerateSignature(): void
    {
        $request = new PurchaseRequest(self::createStub(ClientInterface::class), new Request());
        $request->setSharedSecret('Z3qY08EpvLlAAoMZdnyUdQ==');

        // the sign1 vector of Des3SignTest (openssl CLI)
        self::assertSame(
            'K0+CFnMDIw09Hi8/K+Dwp10Gv8BiVZID',
            $request->generateSignature('000000;0013662162;0900;5.80;EUR;9960012037;0000002313;http://localhost:4444/testserver.php;abc=defgh'),
        );
    }

    public function testPurchaseSendDataRejectsNonArray(): void
    {
        $request = new PurchaseRequest(self::createStub(ClientInterface::class), new Request());

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('Purchase data must be an array');

        $request->sendData('nope');
    }

    public function testCompletePurchaseRequestAccessors(): void
    {
        $request = new CompletePurchaseRequest(self::createStub(ClientInterface::class), new Request());
        $request->initialize(['sharedSecret' => 's', 'vs' => '1', 'ss' => '2', 'amount' => '3']);

        self::assertSame('s', $request->getSharedSecret());
        self::assertSame('1', $request->getVs());
        self::assertSame('2', $request->getSs());
        self::assertSame('3.00', $request->getAmount());
    }

    public function testRedirectUrlNeedsAPurchaseRequest(): void
    {
        $response = new PurchaseResponse(new CompletePurchaseRequest(self::createStub(ClientInterface::class), new Request()), []);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIs('PurchaseResponse requires a PurchaseRequest');

        $response->getRedirectUrl();
    }

    public function testPurchaseResponseWithoutData(): void
    {
        $response = new PurchaseResponse(new PurchaseRequest(self::createStub(ClientInterface::class), new Request()), null);

        self::assertSame([], $response->getRedirectData());
        self::assertSame('https://ib.slsp.sk/epayment/epayment/epayment.xml?', $response->getRedirectUrl());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function responseDataWithoutStrings(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [[]];
        yield 'non-string values' => [['RES' => ['OK'], 'VS' => 1, 'SS' => null, 'AMOUNT' => 5.8, 'CURRENCY' => false, 'REAL' => ['OK']]];
    }

    #[DataProvider('responseDataWithoutStrings')]
    public function testCompletePurchaseResponseWithoutStrings(mixed $data): void
    {
        $response = new CompletePurchaseResponse(new CompletePurchaseRequest(self::createStub(ClientInterface::class), new Request()), $data);

        self::assertFalse($response->isSuccessful());
        self::assertNull($response->getRes());
        self::assertNull($response->getVs());
        self::assertNull($response->getSs());
        self::assertNull($response->getAmount());
        self::assertNull($response->getCurrency());
        self::assertNull($response->getReal());
    }

    /**
     * @return iterable<string, array{class-string, class-string}>
     */
    public static function packageExceptions(): iterable
    {
        yield 'InvalidArgumentException' => [InvalidArgumentException::class, \InvalidArgumentException::class];
        yield 'LogicException' => [LogicException::class, \LogicException::class];
        yield 'RuntimeException' => [RuntimeException::class, \RuntimeException::class];
    }

    /**
     * @param class-string $class
     * @param class-string $splParent
     */
    #[DataProvider('packageExceptions')]
    public function testPackageExceptionHierarchy(string $class, string $splParent): void
    {
        self::assertContains(SporoPayException::class, class_implements($class));
        self::assertContains(OmnipayException::class, class_implements($class));
        self::assertContains($splParent, class_parents($class));
    }
}
