<?php

declare(strict_types=1);

use ESoul\CodingStandard\Config;
use PhpCsFixer\Finder;

return Config::create(
    Finder::create()->files()->in([__DIR__ . '/src', __DIR__ . '/tests']),
    __DIR__ . '/temp/php-cs-fixer.cache',
);
