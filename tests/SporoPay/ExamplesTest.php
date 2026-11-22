<?php

declare(strict_types=1);

namespace Omnipay\Tests\SporoPay;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Runs the examples that the documentation includes, so the snippets keep working.
 * Each in its own process: they send headers.
 */
#[RunTestsInSeparateProcesses]
#[Group('examples')]
final class ExamplesTest extends TestCase
{
    private const string RETURN = '000000;1234567890;0900;000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return.php;order=2024000123';

    /**
     * @param array<string, string> $query
     */
    private function runExample(string $example, array $query = []): string
    {
        $get = $_GET;
        $_GET = $query;
        ob_start();
        try {
            (static function (string $file): void {
                require $file;
            })(__DIR__ . '/../../examples/' . $example);
        } finally {
            $output = (string) ob_get_clean();
            $_GET = $get;
        }

        return $output;
    }

    public function testPurchaseRedirectsWithTheSignedUrl(): void
    {
        $output = $this->runExample('purchase.php');

        // sign1 computed with the openssl CLI (see Des3SignTest)
        self::assertStringContainsString(
            'https://platby.tomaj.sk/payment/sporopay-des?pu_predcislo=000000&amp;pu_cislo=0013662162&amp;pu_kbanky=0900&amp;suma=10.00&amp;mena=EUR&amp;vs=2024000123&amp;ss=0000000001&amp;url=https%3A%2F%2Fshop.example%2Freturn.php&amp;param=order%3D2024000123&amp;sign1=nXibdJCwuNUjifaNVnqLs8oj8j%2FgsQI4',
            $output,
        );
    }

    /**
     * @return array<string, string>
     */
    private static function query(string $return, string $result, string $sign2): array
    {
        $values = explode(';', $return . ';' . $result . ';' . $result);
        $keys = ['u_predcislo', 'u_cislo', 'u_kbanky', 'pu_predcislo', 'pu_cislo', 'pu_kbanky', 'suma', 'mena', 'vs', 'ss', 'url', 'param', 'result', 'real'];

        return array_combine($keys, $values) + ['SIGN2' => $sign2];
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function returns(): iterable
    {
        // SIGN2 computed with the openssl CLI
        yield 'paid' => [self::query(self::RETURN, 'OK', 'iwuQTFfludUL28hmx6APu9lYqW7jN5TM'), "Order 2024000123 paid, 10.00 EUR\n"];
        yield 'failed' => [self::query(self::RETURN, 'NOK', 'fHnwZ/tcgUDaBHB+DKnnh1ATVelNrnE5'), "Order 2024000123 not paid: NOK\n"];
        yield 'tampered' => [self::query(self::RETURN, 'OK', 'fHnwZ/tcgUDaBHB+DKnnh1ATVelNrnE5'), "Invalid return: incorect signature\n"];
        yield 'paid less' => [self::query(str_replace(';10.00;', ';1.00;', self::RETURN), 'OK', 'pkoE/FsEIlu7FrOREKijbYTVWj//PkR+'), "Invalid return: Amount mismatch\n"];
        yield 'unknown order' => [['vs' => '999'], "Unknown order\n"];
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('returns')]
    public function testReturn(array $query, string $expected): void
    {
        self::assertSame($expected, $this->runExample('return.php', $query));
    }
}
