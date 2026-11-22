<?php

declare(strict_types=1);

namespace ESoul\CodingStandard\Tests;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_dir;
use function mkdir;
use function rmdir;
use function str_replace;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const PHP_BINARY;

/**
 * Runs the real php-cs-fixer binary the way a consumer's `.php-cs-fixer.dist.php` would.
 */
final class FixerRunTest extends TestCase
{
    private const string UNFORMATTED = <<<'PHP'
        <?php
        namespace App;
        use Zeta\Beta;
        use Alpha\Gamma;
        class Foo
        {
            public function bar(Beta $a,Gamma $b) : array
            {
                if (is_null($a))
                {
                    return array(1,2);
                }
                return [
                    'x'=>$a . $b
                ];
            }
        }
        PHP;

    private const string FORMATTED = <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App;

        use Alpha\Gamma;
        use Zeta\Beta;

        class Foo
        {
            public function bar(Beta $a, Gamma $b): array {
                if (null === $a) {
                    return [1,2];
                }
                return [
                    'x' => $a . $b,
                ];
            }
        }
        PHP;

    private string $dir;

    protected function setUp(): void {
        $this->dir = sys_get_temp_dir() . '/esoul-coding-standard-' . uniqid('', true);
        mkdir($this->dir . '/src', 0o777, true);
        file_put_contents($this->dir . '/src/Foo.php', self::UNFORMATTED);
        file_put_contents($this->dir . '/.php-cs-fixer.dist.php', str_replace('%AUTOLOAD%', dirname(__DIR__) . '/vendor/autoload.php', <<<'PHP'
            <?php

            use ESoul\CodingStandard\Config;
            use PhpCsFixer\Finder;

            require '%AUTOLOAD%';

            return Config::create(Finder::create()->in(__DIR__ . '/src'), __DIR__ . '/cs.cache');
            PHP));
    }

    protected function tearDown(): void {
        $this->remove($this->dir);
    }

    public function test_it_fixes_a_badly_formatted_file(): void {
        [$exitCode, $output] = $this->runFixer('fix');

        self::assertSame(0, $exitCode, $output);
        self::assertSame(self::FORMATTED . "\n", file_get_contents($this->dir . '/src/Foo.php'));
    }

    public function test_dry_run_reports_violations_with_a_non_zero_exit_code(): void {
        [$exitCode] = $this->runFixer('fix --dry-run');

        self::assertSame(8, $exitCode);
        self::assertSame(self::UNFORMATTED, file_get_contents($this->dir . '/src/Foo.php'));
    }

    public function test_fixed_output_is_stable(): void {
        $this->runFixer('fix');

        [$exitCode, $output] = $this->runFixer('fix --dry-run');

        self::assertSame(0, $exitCode, $output);
    }

    public function test_it_writes_the_cache_to_the_given_file(): void {
        $this->runFixer('fix');

        self::assertFileExists($this->dir . '/cs.cache');
    }

    /**
     * @return array{int, string}
     */
    private function runFixer(string $arguments): array {
        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg(__DIR__ . '/../vendor/bin/php-cs-fixer')
            . ' ' . $arguments
            . ' --config=' . escapeshellarg($this->dir . '/.php-cs-fixer.dist.php')
            . ' --no-interaction 2>&1';
        exec($command, $lines, $exitCode);

        return [$exitCode, implode("\n", $lines)];
    }

    private function remove(string $path): void {
        if ( ! is_dir($path)) {
            unlink($path);

            return;
        }

        foreach (new FilesystemIterator($path, FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS) as $pathname => $_) {
            $this->remove($pathname);
        }
        rmdir($path);
    }
}
