<?php

declare(strict_types=1);

namespace Omnipay\Tests\SporoPay;

use Omnipay\SporoPay\Sign\Des3Sign;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Des3SignTest extends TestCase
{
    private const string SECRET = 'Z3qY08EpvLlAAoMZdnyUdQ==';
    private const string PURCHASE = '000000;0013662162;0900;5.80;EUR;9960012037;0000002313;http://localhost:4444/testserver.php;abc=defgh';

    /**
     * Expected values were computed independently with the openssl CLI:
     * (sha1(input) . 0xFFFFFFFF) encrypted with DES-EDE3-CBC, zero IV, no padding,
     * key = secret (16 bytes: K1 K2 K1, 24 bytes: K1 K2 K3), base64 encoded.
     *
     * @return iterable<string, array{string, string, string}>
     */
    public static function vectors(): iterable
    {
        yield 'purchase sign1' => [self::PURCHASE, self::SECRET, 'K0+CFnMDIw09Hi8/K+Dwp10Gv8BiVZID'];
        yield 'notification SIGN2 OK' => [
            '000000;1234567890;0900;000000;0013662162;0900;5.80;EUR;9960012037;0000002313;http://localhost:4444/testserver.php;abc=defgh;OK;OK',
            self::SECRET,
            'uJmQ7qZPNHy5kJPNt9N1cxa28cLaU5zV',
        ];
        yield 'notification SIGN2 NOK' => [
            '000000;1234567890;0900;000000;0013662162;0900;5.80;EUR;9960012037;0000002313;http://localhost:4444/testserver.php;abc=defgh;NOK;NOK',
            self::SECRET,
            'h0D8FKF+U4JxFPEYvZnUyRwI8XImvZIb',
        ];
        yield '24 byte secret' => [self::PURCHASE, 'ABEiM0RVZneImaq7zN3u/wARIjNEVWZ3', 'OfOENuBbZm8zjaGPk7oIh4Kn5DpZh460'];
        yield 'utf-8 bytes' => [
            '000000;0013662162;0900;1234567.89;EUR;1;2;https://e.sk/r?a=1&b=2;ľščťžýáíé',
            self::SECRET,
            'Hlsc5gzUK4+0MCe3ip0thyjTe2z9A5Ed',
        ];
        // whitespace and missing padding are still accepted by the strict base64 decoder
        yield 'secret with trailing newline' => [self::PURCHASE, self::SECRET . "\n", 'K0+CFnMDIw09Hi8/K+Dwp10Gv8BiVZID'];
        yield 'secret without padding' => [self::PURCHASE, 'Z3qY08EpvLlAAoMZdnyUdQ', 'K0+CFnMDIw09Hi8/K+Dwp10Gv8BiVZID'];
    }

    #[DataProvider('vectors')]
    public function testKnownSignature(string $input, string $secret, string $expected): void
    {
        self::assertSame($expected, (new Des3Sign())->sign($input, $secret));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSecrets(): iterable
    {
        yield 'url-safe alphabet' => ['Z3qY08Ep-LlAAoMZdnyU_Q=='];
        yield 'quoted' => ['"' . self::SECRET . '"'];
        yield 'garbage' => ['not base64!'];
    }

    #[DataProvider('invalidSecrets')]
    public function testInvalidSecretThrows(string $secret): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Shared secret must be base64 encoded');

        (new Des3Sign())->sign(self::PURCHASE, $secret);
    }
}
