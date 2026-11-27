<?php

declare(strict_types=1);

namespace Omnipay\Tests\SporoPay;

use Omnipay\Common\Http\ClientInterface;
use Omnipay\SporoPay\Exception\InvalidArgumentException;
use Omnipay\SporoPay\Gateway;
use Omnipay\SporoPay\Message\CompletePurchaseRequest;
use Omnipay\SporoPay\Message\PurchaseRequest;
use Omnipay\SporoPay\Sign\Des3Sign;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * The shared secret is marked with #[\SensitiveParameter], so PHP replaces it with a
 * SensitiveParameterValue in stack traces (error reports, logs, getTraceAsString()).
 */
final class SensitiveParameterTest extends TestCase
{
    private string|false $ignoreArgs = false;

    #[\Override]
    protected function setUp(): void
    {
        // php.ini-production sets it to On, which drops all arguments; the tests need them
        $this->ignoreArgs = ini_set('zend.exception_ignore_args', '0');
    }

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->ignoreArgs !== false) {
            ini_set('zend.exception_ignore_args', $this->ignoreArgs);
        }
    }

    /**
     * @return iterable<string, array{class-string, string, string}>
     */
    public static function sensitiveParameters(): iterable
    {
        yield 'Des3Sign::sign()' => [Des3Sign::class, 'sign', 'secret'];
        yield 'Gateway::setSharedSecret()' => [Gateway::class, 'setSharedSecret', 'value'];
        yield 'PurchaseRequest::setSharedSecret()' => [PurchaseRequest::class, 'setSharedSecret', 'value'];
        yield 'CompletePurchaseRequest::setSharedSecret()' => [CompletePurchaseRequest::class, 'setSharedSecret', 'value'];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('sensitiveParameters')]
    public function testSecretParameterIsSensitive(string $class, string $method, string $parameter): void
    {
        $sensitive = [];
        foreach (new \ReflectionMethod($class, $method)->getParameters() as $reflection) {
            if ($reflection->getAttributes(\SensitiveParameter::class) !== []) {
                $sensitive[] = $reflection->getName();
            }
        }

        self::assertSame([$parameter], $sensitive);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSecrets(): iterable
    {
        yield 'not base64' => ['secret-key!'];
        yield 'wrong length' => ['c2VjcmV0LWtleS0xMjM0']; // base64 of a 15-byte key
    }

    /**
     * Des3Sign::sign() throws for an invalid key, with the key on the stack.
     */
    #[DataProvider('invalidSecrets')]
    public function testSecretIsRedactedInTraceOfSignException(string $secret): void
    {
        try {
            new Des3Sign()->sign('data', $secret);
            self::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertRedacted($e, $secret);
        }
    }

    /**
     * @return iterable<string, array{class-string<Gateway|PurchaseRequest|CompletePurchaseRequest>}>
     */
    public static function setters(): iterable
    {
        yield 'Gateway' => [Gateway::class];
        yield 'PurchaseRequest' => [PurchaseRequest::class];
        yield 'CompletePurchaseRequest' => [CompletePurchaseRequest::class];
    }

    /**
     * A secret of the wrong type (an int read from a config file) throws a TypeError with the setter on the stack.
     *
     * @param class-string<Gateway|PurchaseRequest|CompletePurchaseRequest> $class
     */
    #[DataProvider('setters')]
    public function testSecretIsRedactedInTraceOfSetterTypeError(string $class): void
    {
        $object = new $class(self::createStub(ClientInterface::class), new HttpRequest());
        $secret = 987654321987;

        try {
            $object->setSharedSecret($secret); // @phpstan-ignore argument.type (the TypeError is the point)
            self::fail('Expected TypeError');
        } catch (\TypeError $e) {
            $this->assertRedacted($e, (string) $secret);
        }
    }

    private function assertRedacted(\Throwable $e, string $secret): void
    {
        $frame = $e->getTrace()[0];
        self::assertArrayHasKey('args', $frame);
        $redacted = array_values(array_filter($frame['args'], static fn(mixed $arg): bool => $arg instanceof \SensitiveParameterValue));
        self::assertCount(1, $redacted);
        $value = $redacted[0]->getValue();
        self::assertSame($secret, is_int($value) ? (string) $value : $value);
        // only the frame of the call that took the secret: the test method's own data-provider argument is on the stack too
        $line = explode("\n", $e->getTraceAsString())[0];
        self::assertStringContainsString('Object(SensitiveParameterValue)', $line);
        self::assertStringNotContainsString($secret, $line);
    }
}
