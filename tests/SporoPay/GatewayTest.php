<?php

declare(strict_types=1);

namespace Omnipay\Tests\SporoPay;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\SporoPay\Gateway;
use Omnipay\SporoPay\Message\CompletePurchaseResponse;
use Omnipay\SporoPay\Sign\Des3Sign;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class GatewayTest extends TestCase
{
    private const string SHARED_SECRET = 'Z3qY08EpvLlAAoMZdnyUdQ==';

    private Gateway $gateway;

    #[\Override]
    protected function setUp(): void
    {
        $this->gateway = new Gateway(self::createStub(ClientInterface::class), new Request());
        $this->gateway->setSharedSecret(self::SHARED_SECRET);
    }

    public function testName(): void
    {
        self::assertSame('SporoPay SLSP Gateway', $this->gateway->getName());
    }

    public function testPurchaseSign(): void
    {
        $request = $this->gateway->purchase($this->purchaseParameters());

        self::assertSame('5.80', $request->getAmount());

        $response = $request->send();

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

        self::assertStringStartsWith('https://platby.tomaj.sk/payment/sporopay-des?', $response->getRedirectUrl());
    }

    public function testPurchaseInTestModeUsesConfiguredTestHost(): void
    {
        $this->gateway->setTestMode(true);
        $this->gateway->setTestHost('http://localhost:8080');
        $response = $this->gateway->purchase($this->purchaseParameters())->send();

        self::assertStringStartsWith('http://localhost:8080/payment/sporopay-des?', $response->getRedirectUrl());
    }

    public function testPurchaseRequiresParameters(): void
    {
        $this->expectException(InvalidRequestException::class);

        $this->gateway->purchase(['amount' => '5.80'])->send();
    }

    public function testCompletePurchaseWithValidSignature(): void
    {
        $query = $this->signedNotification('OK');

        $response = $this->complete($query);

        self::assertTrue($response->isSuccessful());
        self::assertSame('OK', $response->getRes());
        self::assertSame('9960012037', $response->getVs());
    }

    public function testCompletePurchaseWithFailedPayment(): void
    {
        $query = $this->signedNotification('NOK');

        $response = $this->complete($query);

        self::assertFalse($response->isSuccessful());
        self::assertSame('NOK', $response->getRes());
    }

    public function testCompletePurchaseWithInvalidSignature(): void
    {
        $query = $this->signedNotification('OK');
        $query['suma'] = '9.99';

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('incorect signature');

        $this->complete($query);
    }

    public function testCompletePurchaseWithKnownSignature(): void
    {
        // SIGN2 computed independently with the openssl CLI (see Des3SignTest)
        $query = $this->notification('OK');
        $query['SIGN2'] = 'uJmQ7qZPNHy5kJPNt9N1cxa28cLaU5zV';

        $response = $this->complete($query);

        self::assertTrue($response->isSuccessful());
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function tamperedNotifications(): iterable
    {
        yield 'amount' => ['suma', '5.81'];
        yield 'variable symbol' => ['vs', '9960012038'];
        yield 'result' => ['result', 'NOK'];
        yield 'real' => ['real', 'NOK'];
        yield 'payer account' => ['u_cislo', '1234567891'];
        yield 'lower-case SIGN2' => ['SIGN2', strtolower('uJmQ7qZPNHy5kJPNt9N1cxa28cLaU5zV')];
        yield 'SIGN2 with trailing space' => ['SIGN2', 'uJmQ7qZPNHy5kJPNt9N1cxa28cLaU5zV '];
        yield 'empty SIGN2' => ['SIGN2', ''];
        yield 'array SIGN2' => ['SIGN2', ['uJmQ7qZPNHy5kJPNt9N1cxa28cLaU5zV']];
    }

    #[DataProvider('tamperedNotifications')]
    public function testCompletePurchaseRejectsTamperedNotification(string $key, mixed $value): void
    {
        $query = $this->notification('OK');
        $query['SIGN2'] = 'uJmQ7qZPNHy5kJPNt9N1cxa28cLaU5zV';
        $query[$key] = $value;

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('incorect signature');

        $this->complete($query);
    }

    public function testCompletePurchaseWithWrongSecret(): void
    {
        $query = $this->notification('OK');
        $query['SIGN2'] = 'uJmQ7qZPNHy5kJPNt9N1cxa28cLaU5zV';
        $this->gateway->setSharedSecret('ABEiM0RVZneImaq7zN3u/wARIjNEVWZ3');

        $this->expectException(InvalidRequestException::class);

        $this->complete($query);
    }

    public function testCompletePurchaseWithoutSignature(): void
    {
        $query = ['vs' => '9960012037', 'result' => 'OK'];

        $this->expectException(InvalidRequestException::class);

        $this->complete($query);
    }

    /**
     * Valid returns (SIGN2 computed with the openssl CLI, see Des3SignTest) that belong to another payment.
     *
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function otherPayments(): iterable
    {
        yield 'other variable symbol' => ['vs', '111', 'CWTU8KrnUbJgtb9aCXjKpTDQ7VNVz5/+', 'Variable symbol mismatch'];
        yield 'other amount' => ['suma', '1.00', '/zr/fTO94eIgup8KG14y3XTDLDrmfuIV', 'Amount mismatch'];
        yield 'other currency' => ['mena', 'CZK', '5bssy0imgZ2JxNevUPfivjMbiC5bAypQ', 'Currency mismatch'];
    }

    #[DataProvider('otherPayments')]
    public function testCompletePurchaseRejectsReturnOfAnotherPayment(string $key, string $value, string $sign2, string $message): void
    {
        $query = $this->notification('OK');
        $query[$key] = $value;
        $query['SIGN2'] = $sign2;

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs($message);

        $this->complete($query);
    }

    public function testCompletePurchaseAcceptsTheExpectedPayment(): void
    {
        $query = $this->notification('OK');
        $query['vs'] = '111';
        $query['SIGN2'] = 'CWTU8KrnUbJgtb9aCXjKpTDQ7VNVz5/+';

        $response = $this->complete($query, ['vs' => '111', 'amount' => '5.8', 'ss' => '0000002313']);

        self::assertTrue($response->isSuccessful());
        self::assertSame('111', $response->getVs());
        self::assertSame('0000002313', $response->getSs());
        self::assertSame('5.80', $response->getAmount());
        self::assertSame('EUR', $response->getCurrency());
        self::assertSame('OK', $response->getReal());
        self::assertSame(['RES' => 'OK', 'VS' => '111', 'SS' => '0000002313', 'AMOUNT' => '5.80', 'CURRENCY' => 'EUR', 'REAL' => 'OK'], $response->getData());
    }

    public function testCompletePurchaseRejectsAnotherSpecificSymbol(): void
    {
        $query = $this->notification('OK');
        $query['ss'] = '0000009999';
        $query['SIGN2'] = 'f2bHZD4OS05vDUL169SgpEXTmSFLElyK';

        // without an expected ss, any signed ss is accepted
        self::assertSame('0000009999', $this->complete($query)->getSs());

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('Specific symbol mismatch');

        $this->complete($query, ['vs' => '9960012037', 'amount' => '5.80', 'ss' => '0000002313']);
    }

    public function testRealIsExposedAsSigned(): void
    {
        $query = $this->notification('OK');
        $query['real'] = 'NOK';
        $query['SIGN2'] = 'se8hxAYo+t5yE0e5+5JRkg+zEIS/mmX9';

        $response = $this->complete($query);

        self::assertSame('OK', $response->getRes());
        self::assertSame('NOK', $response->getReal());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function missingExpectations(): iterable
    {
        yield 'no vs' => [['amount' => '5.80'], 'The vs parameter is required'];
        yield 'empty vs' => [['vs' => '', 'amount' => '5.80'], 'The vs parameter is required'];
        yield 'no amount' => [['vs' => '9960012037'], 'The amount parameter is required'];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[DataProvider('missingExpectations')]
    public function testCompletePurchaseRequiresTheExpectedPayment(array $parameters, string $message): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs($message);

        $this->complete($this->signedNotification('OK'), $parameters);
    }

    /**
     * Completes a purchase with the gateway's settings and $query as the bank's return request.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $parameters
     */
    private function complete(array $query, array $parameters = ['vs' => '9960012037', 'amount' => '5.80']): CompletePurchaseResponse
    {
        $gateway = new Gateway(self::createStub(ClientInterface::class), new Request($query));
        $gateway->setSharedSecret($this->gateway->getSharedSecret());

        return $gateway->completePurchase($parameters)->send();
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
        $query = $this->notification($result);
        $query['SIGN2'] = new Des3Sign()->sign(implode(';', $query), self::SHARED_SECRET);

        return $query;
    }

    /**
     * @return array<string, string>
     */
    private function notification(string $result): array
    {
        return [
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
    }
}
