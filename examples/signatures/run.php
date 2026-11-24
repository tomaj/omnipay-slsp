<?php

declare(strict_types=1);

// sign1 and SIGN2 computed by the library and, independently, by the openssl command line:
// SHA-1 of the string, padded with 0xFF to 24 bytes, DES-EDE3-CBC with a zero IV and no padding, base64.

use Omnipay\SporoPay\Exception\InvalidArgumentException;
use Omnipay\SporoPay\Sign\Des3Sign;

require __DIR__ . '/vendor/autoload.php';

/**
 * Runs a command with $input on stdin and returns its stdout.
 *
 * @param list<string> $command
 */
function run(array $command, string $input): string
{
    $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w']], $pipes);
    if ($process === false) {
        throw new RuntimeException('cannot run ' . $command[0]);
    }
    fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $output = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    proc_close($process);

    return $output;
}

function opensslSign(string $data, string $secret): string
{
    $key = bin2hex((string) base64_decode($secret, true));
    if (strlen($key) === 32) {
        $key .= substr($key, 0, 16);   // a 16-byte key K1 K2 is used as K1 K2 K1
    }
    $hash = run(['openssl', 'dgst', '-sha1', '-binary'], $data) . str_repeat("\xFF", 4);

    return base64_encode(run(['openssl', 'enc', '-des-ede3-cbc', '-K', $key, '-iv', '0000000000000000', '-nopad'], $hash));
}

$sign = new Des3Sign();
$cases = [
    'sign1 (payment request)' => [
        '000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return;order=2024000123',
        'Z3qY08EpvLlAAoMZdnyUdQ==',
    ],
    'SIGN2 (return, result OK)' => [
        '000000;1234567890;0900;000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return;order=2024000123;OK;OK',
        'Z3qY08EpvLlAAoMZdnyUdQ==',
    ],
    'sign1 with a 24-byte key' => [
        '000000;0013662162;0900;10.00;EUR;2024000123;0000000001;https://shop.example/return;order=2024000123',
        'MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0',
    ],
];
foreach ($cases as $name => [$data, $secret]) {
    $library = $sign->sign($data, $secret);
    $openssl = opensslSign($data, $secret);
    echo "{$name}\n  {$data}\n  library {$library}\n  openssl {$openssl}\n  equal   " . var_export($library === $openssl, true) . "\n";
}

echo "\nshared secrets that are not strict base64, empty or not a 16- or 24-byte key:\n";
foreach (['"Z3qY08EpvLlAAoMZdnyUdQ=="', 'Z3qY08EpvLlAAoMZdnyUdQ', 'Z3qY08Ep-LlAAoMZdnyUdQ==', '', 'MTIzNDU2Nzg='] as $secret) {
    try {
        $result = 'accepted, ' . $sign->sign('abc', $secret);
    } catch (InvalidArgumentException $e) {
        $result = $e::class . ': ' . $e->getMessage();
    }
    printf("  %-28s %s\n", $secret === '' ? "''" : $secret, $result);
}
