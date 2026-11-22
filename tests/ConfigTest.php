<?php

declare(strict_types=1);

namespace ESoul\CodingStandard\Tests;

use ESoul\CodingStandard\Config;
use PhpCsFixer\Config as PhpCsFixerConfig;
use PhpCsFixer\Finder;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function test_it_uses_the_given_finder(): void {
        $finder = Finder::create()->in(__DIR__);

        $config = Config::create($finder);

        self::assertSame($finder, $config->getFinder());
    }

    public function test_it_uses_the_given_cache_file(): void {
        $config = Config::create(Finder::create()->in(__DIR__), '/tmp/esoul/php-cs-fixer.cache');

        self::assertTrue($config->getUsingCache());
        self::assertSame('/tmp/esoul/php-cs-fixer.cache', $config->getCacheFile());
    }

    public function test_it_keeps_the_default_cache_handling_without_a_cache_file(): void {
        $default = new PhpCsFixerConfig();

        $config = Config::create(Finder::create()->in(__DIR__));

        self::assertSame($default->getUsingCache(), $config->getUsingCache());
        self::assertSame($default->getCacheFile(), $config->getCacheFile());
    }

    public function test_it_allows_risky_rules_and_enables_them(): void {
        $config = Config::create(Finder::create()->in(__DIR__));

        self::assertTrue($config->getRiskyAllowed());
        self::assertTrue($config->getRules()['declare_strict_types'] ?? false);
        self::assertTrue($config->getRules()['is_null'] ?? false);
    }

    public function test_rules_can_be_extended_by_consumers(): void {
        $config = Config::create(Finder::create()->in(__DIR__))
            ->setRules([...Config::rules(), 'yoda_style' => true]);

        self::assertTrue($config->getRules()['yoda_style'] ?? false);
        self::assertTrue($config->getRules()['@PSR12'] ?? false);
    }
}
