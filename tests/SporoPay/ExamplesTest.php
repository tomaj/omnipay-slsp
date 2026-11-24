<?php

declare(strict_types=1);

namespace Omnipay\Tests\SporoPay;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Runs every example in examples/<name>/ (the ones the documentation includes) against this checkout
 * and compares its output with the example's expected-output.txt.
 *
 * The examples workflow does the same with a real `composer install` of each example; this test runs
 * them on every PHP version of the CI matrix. Each example is copied to a temporary directory whose
 * vendor/autoload.php loads this repository's autoloader. An example with an e2e.sh (it starts its own
 * servers with `php -S`) runs through bash, the others with `php run.php`. `php` is the PHP running this test,
 * with errors on stderr: the example must not write to stderr, except deprecations raised inside vendor/.
 */
#[Group('examples')]
final class ExamplesTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function examples(): iterable
    {
        $directories = glob(__DIR__ . '/../../examples/*/expected-output.txt');
        foreach ($directories === false ? [] : $directories as $file) {
            yield basename(dirname($file)) => [dirname($file)];
        }
    }

    public function testEveryExampleHasAnExpectedOutput(): void
    {
        $directories = glob(__DIR__ . '/../../examples/*', GLOB_ONLYDIR);
        self::assertNotFalse($directories);
        $examples = array_values(array_diff(array_map(basename(...), $directories), ['ci']));

        self::assertGreaterThanOrEqual(3, count($examples));
        self::assertSame($examples, array_keys(iterator_to_array(self::examples())));
    }

    #[DataProvider('examples')]
    public function testExampleOutput(string $directory): void
    {
        $work = sys_get_temp_dir() . '/omnipay-example-' . bin2hex(random_bytes(6));
        $this->copy($directory, $work);
        mkdir($work . '/vendor');
        file_put_contents($work . '/vendor/autoload.php', '<?php return require ' . var_export(__DIR__ . '/../../vendor/autoload.php', true) . ";\n");

        // `php` for run.php and e2e.sh (and the servers it starts): this PHP, errors logged to stderr, never into the output
        mkdir($work . '/.bin');
        file_put_contents($work . '/.bin/php', "#!/bin/sh\nexec " . escapeshellarg(PHP_BINARY) . " -d display_errors=0 -d log_errors=1 -d error_reporting=-1 \"\$@\"\n");
        chmod($work . '/.bin/php', 0o755);

        try {
            $command = is_file($work . '/e2e.sh') ? ['bash', 'e2e.sh'] : [$work . '/.bin/php', 'run.php'];
            $path = getenv('PATH');
            $env = ['PATH' => $work . '/.bin' . PATH_SEPARATOR . (is_string($path) && $path !== '' ? $path : '/usr/bin:/bin')];
            $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $work, $env);
            self::assertIsResource($process);
            $stdout = (string) stream_get_contents($pipes[1]);
            $stderr = (string) stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $status = proc_close($process);
        } finally {
            $this->remove($work);
        }

        self::assertSame(0, $status, $stdout . $stderr);
        self::assertSame((string) file_get_contents($directory . '/expected-output.txt'), $stdout, $stderr);
        self::assertSame('', $this->withoutDependencyDeprecations($stderr));
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

    private function copy(string $from, string $to): void
    {
        mkdir($to);
        $entries = scandir($from);
        foreach ($entries === false ? [] : $entries as $entry) {
            if (in_array($entry, ['.', '..', 'vendor', 'composer.lock'], true)) {
                continue;
            }
            if (is_dir("{$from}/{$entry}")) {
                $this->copy("{$from}/{$entry}", "{$to}/{$entry}");
            } else {
                copy("{$from}/{$entry}", "{$to}/{$entry}");
            }
        }
    }

    private function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }
        $entries = scandir($path);
        foreach ($entries === false ? [] : $entries as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $this->remove("{$path}/{$entry}");
            }
        }
        rmdir($path);
    }
}
