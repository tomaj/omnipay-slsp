<?php

declare(strict_types=1);

namespace Omnipay\Tests\SporoPay;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Runs every ```php block of README.md, UPGRADE-4.0.md and docs/ against the current code.
 *
 * - A block runs in its own PHP process with the Composer autoloader and these variables already defined
 *   (see prepend()): `$gateway` (the simulator's key, test mode), `$sharedSecret`, `$order` and `$orders`
 *   (order 2024000123 for 10.00 EUR, specific symbol 0000000001), `$symfonyRequest` and `$httpClient`. The
 *   query string is a genuine paid return of order 2024000123, so `completePurchase()` works as on a return URL.
 *   It must exit with status 0 without writing to stderr.
 * - A block directly after `<!-- snippet: skip -->` is not run (code that cannot run on its own, or on
 *   purpose shows code that no longer works, such as the 3.x side of an upgrade step).
 * - A ```text block directly after `<!-- snippet: output -->` is the output of the PHP block before it.
 *   Both are compared line by line, ignoring trailing whitespace and blank lines.
 *
 * Longer samples live in examples/ and run in ExamplesTest.
 */
#[Group('examples')]
final class DocsSnippetsTest extends TestCase
{
    private const string SKIP = '<!-- snippet: skip -->';
    private const string OUTPUT = '<!-- snippet: output -->';

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function snippetProvider(): iterable
    {
        $root = dirname(__DIR__, 2);
        $files = [$root . '/README.md', $root . '/UPGRADE-4.0.md'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/docs', \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            \assert($file instanceof \SplFileInfo);
            $path = $file->getPathname();
            if ($file->getExtension() === 'md' && !str_contains($path, '/node_modules/') && !str_contains($path, '/.vitepress/')) {
                $files[] = $path;
            }
        }
        sort($files);

        foreach ($files as $path) {
            $relative = substr($path, strlen($root) + 1);
            foreach (self::blocks((string) file_get_contents($path)) as $line => [$code, $output]) {
                yield $relative . ':' . $line => [$code, $output];
            }
        }
    }

    public function testDocumentationHasSnippets(): void
    {
        $outputs = 0;
        $count = 0;
        foreach (self::snippetProvider() as [, $output]) {
            $count++;
            if ($output !== null) {
                $outputs++;
            }
        }

        self::assertGreaterThan(10, $count);
        self::assertGreaterThan(8, $outputs);
    }

    #[DataProvider('snippetProvider')]
    public function testSnippetRuns(string $code, ?string $expectedOutput): void
    {
        $script = tempnam(sys_get_temp_dir(), 'snippet');
        $prepend = tempnam(sys_get_temp_dir(), 'prepend');
        self::assertIsString($script);
        self::assertIsString($prepend);

        try {
            file_put_contents($script, "<?php\n\ndeclare(strict_types=1);\n\n" . $code);
            file_put_contents($prepend, $this->prepend());

            $process = proc_open(
                [PHP_BINARY, '-d', 'auto_prepend_file=' . $prepend, '-d', 'display_errors=stderr', '-d', 'error_reporting=-1', $script],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            self::assertIsResource($process);
            $stdout = (string) stream_get_contents($pipes[1]);
            $stderr = (string) stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $status = proc_close($process);
        } finally {
            unlink($script);
            unlink($prepend);
        }

        self::assertSame('', $this->withoutDependencyDeprecations($stderr), "The snippet wrote to stderr:\n" . $code);
        self::assertSame(0, $status, "The snippet failed:\n" . $code . "\n" . $stdout);
        if ($expectedOutput !== null) {
            self::assertSame($this->normalize($expectedOutput), $this->normalize($stdout), "The documented output differs:\n" . $code);
        }
    }

    private function prepend(): string
    {
        $autoload = var_export(__DIR__ . '/../../vendor/autoload.php', true);

        return <<<PHP
            <?php
            require {$autoload};
            // a genuine paid return of order 2024000123, signed with the simulator's key (sha1, 0xFF padding, 3DES)
            \$_GET = [
                'u_predcislo' => '000000', 'u_cislo' => '1234567890', 'u_kbanky' => '0900',
                'pu_predcislo' => '000000', 'pu_cislo' => '0013662162', 'pu_kbanky' => '0900',
                'suma' => '10.00', 'mena' => 'EUR', 'vs' => '2024000123', 'ss' => '0000000001',
                'url' => 'https://shop.example/return', 'param' => 'order=2024000123', 'result' => 'OK', 'real' => 'OK',
            ];
            \$key = base64_decode('Z3qY08EpvLlAAoMZdnyUdQ==');
            \$_GET['SIGN2'] = base64_encode(openssl_encrypt(sha1(implode(';', \$_GET), true) . str_repeat("\\xFF", 4), 'DES-EDE3-CBC', \$key . substr(\$key, 0, 8), OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, str_repeat("\\0", 8)));
            unset(\$key);
            \$sharedSecret = 'Z3qY08EpvLlAAoMZdnyUdQ==';
            \$gateway = new Omnipay\\SporoPay\\Gateway();
            \$gateway->setSharedSecret(\$sharedSecret);
            \$gateway->setTestMode(true);
            \$order = (object) ['vs' => '2024000123', 'ss' => '0000000001', 'amount' => '10.00'];
            \$orders = ['2024000123' => ['vs' => '2024000123', 'ss' => '0000000001', 'amount' => '10.00']];
            \$symfonyRequest = Symfony\\Component\\HttpFoundation\\Request::createFromGlobals();
            \$httpClient = new Omnipay\\Common\\Http\\Client();

            PHP;
    }

    /**
     * @return array<int, array{string, ?string}> line number => [php code, expected output]
     */
    private static function blocks(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $code = [];
        $outputs = [];
        $lastPhp = null;
        $previous = '';
        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            if (preg_match('/^```(\w+)/', $lines[$i], $match) !== 1) {
                if (trim($lines[$i]) !== '') {
                    $previous = trim($lines[$i]);
                }
                continue;
            }
            $start = $i + 1;
            $body = [];
            for ($i++; $i < $count && !str_starts_with($lines[$i], '```'); $i++) {
                $body[] = $lines[$i];
            }
            $text = implode("\n", $body) . "\n";
            if ($match[1] === 'php' && $previous !== self::SKIP) {
                $code[$start] = (string) preg_replace('/^<\?php\s*/', '', $text);
                $lastPhp = $start;
            } elseif ($match[1] === 'text' && $previous === self::OUTPUT) {
                self::assertNotNull($lastPhp, 'An output block must follow a PHP block (line ' . $start . ').');
                $outputs[$lastPhp] = $text;
            }
            $previous = '```';
        }

        $blocks = [];
        foreach ($code as $line => $php) {
            $blocks[$line] = [$php, $outputs[$line] ?? null];
        }

        return $blocks;
    }

    private function normalize(string $text): string
    {
        $lines = array_filter(array_map(rtrim(...), explode("\n", $text)), static fn(string $line): bool => $line !== '');

        return implode("\n", $lines);
    }

    /**
     * Deprecations raised inside dependencies are not ours to fix (like ignoreIndirectDeprecations in
     * phpunit.xml.dist): omnipay/common calls is_integer(), deprecated in PHP 8.6.
     */
    private function withoutDependencyDeprecations(string $stderr): string
    {
        $lines = preg_split('~\R~', $stderr);
        $lines = array_filter(
            $lines === false ? [$stderr] : $lines,
            static fn(string $line): bool => trim($line) !== '' && preg_match('~^(PHP )?Deprecated:\s.* in \S+/vendor/\S+ on line \d+$~', $line) !== 1,
        );

        return implode("\n", $lines);
    }
}
