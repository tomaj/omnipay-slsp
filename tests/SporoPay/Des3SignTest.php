<?php

declare(strict_types=1);

namespace Omnipay\Tests\SporoPay;

use Omnipay\SporoPay\Sign\Des3Sign;
use PHPUnit\Framework\TestCase;

final class Des3SignTest extends TestCase
{
    public function testKnownSignature(): void
    {
        $input = '000000;0013662162;0900;5.80;EUR;9960012037;0000002313;http://localhost:4444/testserver.php;abc=defgh';

        self::assertSame('K0+CFnMDIw09Hi8/K+Dwp10Gv8BiVZID', (new Des3Sign())->sign($input, 'Z3qY08EpvLlAAoMZdnyUdQ=='));
    }
}
