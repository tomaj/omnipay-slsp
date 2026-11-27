<?php

declare(strict_types=1);

namespace Omnipay\Tests\SporoPay;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\SporoPay\Message\PurchaseRequest;
use Omnipay\SporoPay\Message\PurchaseResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Format checks of the purchase parameters: they throw from getData(), before anything is signed.
 */
final class PurchaseRequestTest extends TestCase
{
    /**
     * @param array<string, mixed> $parameters
     */
    private function send(array $parameters): PurchaseResponse
    {
        return $this->request($parameters)->send();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function request(array $parameters): PurchaseRequest
    {
        $request = new PurchaseRequest(self::createStub(ClientInterface::class), new Request());
        $request->initialize($parameters + [
            'sharedSecret' => 'Z3qY08EpvLlAAoMZdnyUdQ==',
            'amount' => '10.00',
            'accountNumberPrefix' => '000000',
            'accountNumber' => '0013662162',
            'vs' => '2024000123',
            'ss' => '0000000001',
            'rurl' => 'https://shop.example/return',
            'param' => 'order=2024000123',
        ]);

        return $request;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSymbols(): iterable
    {
        yield 'empty' => [''];
        yield '11 digits' => ['12345678901'];
        yield 'letters' => ['abc'];
        yield 'letter and digits' => ['2024a00123'];
        yield 'inner space' => ['12 34'];
        yield 'leading space' => [' 1234'];
        yield 'trailing newline' => ["1234\n"];
        yield 'negative' => ['-1'];
        yield 'plus sign' => ['+1'];
        yield 'decimal' => ['1.5'];
        yield 'hex' => ['0x1A'];
        yield 'non-ASCII digits' => ['١٢٣'];
    }

    #[DataProvider('invalidSymbols')]
    public function testInvalidVariableSymbolIsRejectedBeforeSigning(string $vs): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The vs parameter must be up to 10 digits');

        $this->send(['vs' => $vs]);
    }

    #[DataProvider('invalidSymbols')]
    public function testInvalidSpecificSymbolIsRejectedBeforeSigning(string $ss): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The ss parameter must be up to 10 digits');

        $this->send(['ss' => $ss]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validSymbols(): iterable
    {
        yield 'one digit' => ['1'];
        yield 'zero' => ['0'];
        yield '10 digits' => ['9999999999'];
        yield 'leading zeros' => ['0000000123'];
    }

    #[DataProvider('validSymbols')]
    public function testValidSymbols(string $symbol): void
    {
        $data = $this->request(['vs' => $symbol, 'ss' => $symbol])->getData();

        self::assertSame($symbol, $data['vs']);
        self::assertSame($symbol, $data['ss']);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function invalidAccounts(): iterable
    {
        $prefix = 'The pu_predcislo parameter must be up to 6 digits';
        $number = 'The pu_cislo parameter must be up to 10 digits';
        yield 'empty prefix' => ['', '0013662162', $prefix];
        yield '7 digit prefix' => ['0000000', '0013662162', $prefix];
        yield 'prefix with a dash' => ['000-00', '0013662162', $prefix];
        yield 'prefix with a space' => ['000 00', '0013662162', $prefix];
        yield 'prefix with a newline' => ["000000\n", '0013662162', $prefix];
        yield 'prefix with a leading letter' => ['x00000', '0013662162', $prefix];
        yield 'empty number' => ['000000', '', $number];
        yield '11 digit number' => ['000000', '00136621620', $number];
        yield 'number with a dash' => ['000000', '0013-662162', $number];
        yield 'number with a space' => ['000000', '0013 662162', $number];
        yield 'number with a newline' => ['000000', "0013662162\n", $number];
        yield 'number with a bank code' => ['000000', '0013662162/0900', $number];
        yield 'IBAN' => ['000000', 'SK3109000000000013662162', $number];
    }

    #[DataProvider('invalidAccounts')]
    public function testInvalidAccountIsRejectedBeforeSigning(string $prefix, string $number, string $message): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs($message);

        $this->send(['accountNumberPrefix' => $prefix, 'accountNumber' => $number]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validAccounts(): iterable
    {
        yield 'zero prefix' => ['000000', '0013662162'];
        yield 'short' => ['0', '1'];
        yield 'with prefix' => ['123456', '9999999999'];
    }

    #[DataProvider('validAccounts')]
    public function testValidAccount(string $prefix, string $number): void
    {
        $data = $this->request(['accountNumberPrefix' => $prefix, 'accountNumber' => $number])->getData();

        self::assertSame($prefix, $data['pu_predcislo']);
        self::assertSame($number, $data['pu_cislo']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidReturnUrls(): iterable
    {
        yield 'empty' => [''];
        yield 'relative path' => ['/return'];
        yield 'no scheme' => ['shop.example/return'];
        yield 'protocol-relative' => ['//shop.example/return'];
        yield 'scheme only' => ['https://'];
        yield 'no host' => ['https:///return'];
        yield 'opaque' => ['https:shop.example'];
        yield 'ftp' => ['ftp://shop.example/return'];
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'https without slashes' => ['https:/shop.example/return'];
        yield 'space' => ['https://shop.example/re turn'];
        yield 'leading space' => [' https://shop.example/return'];
        yield 'trailing newline' => ["https://shop.example/return\n"];
        yield 'tab' => ["https://shop.example/\treturn"];
        yield 'NUL' => ["https://shop.example/\0return"];
        yield 'DEL' => ["https://shop.example/\x7Freturn"];
        yield 'semicolon' => ['https://shop.example/return;a=1'];
    }

    #[DataProvider('invalidReturnUrls')]
    public function testInvalidReturnUrlIsRejectedBeforeSigning(string $rurl): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The rurl parameter must be an absolute https:// URL');

        $this->send(['rurl' => $rurl]);
    }

    #[DataProvider('invalidReturnUrls')]
    public function testInvalidReturnUrlIsRejectedInTestMode(string $rurl): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The rurl parameter must be an absolute https:// or http:// URL');

        $this->send(['rurl' => $rurl, 'testMode' => true]);
    }

    public function testPlainHttpReturnUrlIsRejectedInProduction(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The rurl parameter must be an absolute https:// URL');

        $this->send(['rurl' => 'http://shop.example/return']);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function validReturnUrls(): iterable
    {
        yield 'https' => ['https://shop.example/return', false];
        yield 'https with query and fragment' => ['https://shop.example:8443/return?order=1&a=b#x', false];
        yield 'upper-case scheme' => ['HTTPS://shop.example/return', false];
        yield 'non-ASCII path' => ['https://obchod.example/návrat', false];
        yield 'https in test mode' => ['https://shop.example/return', true];
        yield 'http in test mode' => ['http://localhost:8080/return', true];
        yield 'upper-case http in test mode' => ['HTTP://127.0.0.1/return', true];
    }

    #[DataProvider('validReturnUrls')]
    public function testValidReturnUrl(string $rurl, bool $testMode): void
    {
        $data = $this->request(['rurl' => $rurl, 'testMode' => $testMode])->getData();

        self::assertSame($rurl, $data['url']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidParams(): iterable
    {
        yield 'newline' => ["order=1\nx=2"];
        yield 'carriage return' => ["order=1\r"];
        yield 'tab' => ["order=\t1"];
        yield 'NUL' => ["order=1\0"];
        yield 'unit separator' => ["order=1\x1F"];
        yield 'DEL' => ["order=1\x7F"];
        yield 'semicolon' => ['order=1;user=5'];
        yield 'only a semicolon' => [';'];
    }

    #[DataProvider('invalidParams')]
    public function testParamWithControlCharactersOrSeparatorIsRejectedBeforeSigning(string $param): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The param parameter must not contain control characters or ";"');

        $this->send(['param' => $param]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validParams(): iterable
    {
        yield 'empty' => [''];
        yield 'key=value' => ['order=2024000123'];
        yield 'spaces and punctuation' => ['order 1, user=5 & a/b?c:d'];
        yield 'non-ASCII' => ['objednávka č. 1'];
    }

    #[DataProvider('validParams')]
    public function testValidParam(string $param): void
    {
        $data = $this->request(['param' => $param])->getData();

        self::assertSame($param, $data['param']);
    }
}
