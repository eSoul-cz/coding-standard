# esoul-cz/coding-standard

Shared [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) ruleset for eSoul PHP packages and applications.

The ruleset is the one the eSoul application template ships in its `.php-cs-fixer.php` (PSR-12 plus the eSoul additions:
same-line function and control-structure braces, ordered imports, short arrays, trailing commas in multiline lists,
`declare(strict_types=1)`, and so on). It lives here so every repository uses the same rules instead of a copied file.
Risky rules are enabled.

## Install

```bash
composer require --dev esoul-cz/coding-standard
```

## Usage

`.php-cs-fixer.dist.php`:

```php
<?php

use ESoul\CodingStandard\Config;
use PhpCsFixer\Finder;

return Config::create(
    Finder::create()->files()->in([__DIR__ . '/src', __DIR__ . '/tests']),
    __DIR__ . '/temp/php-cs-fixer.cache',
);
```

`Config::create(Finder $finder, ?string $cacheFile = null)` returns a regular `PhpCsFixer\Config`, so you can still call
its setters. Without `$cacheFile`, PHP-CS-Fixer's default cache handling applies. `Config::rules()` returns the rules
array if you need to extend it:

```php
return Config::create($finder)->setRules([...Config::rules(), 'yoda_style' => false]);
```

The config allows PHP-CS-Fixer to run on a PHP version newer than the syntax it knows (`setUnsupportedPhpVersionAllowed(true)`),
so `composer cs` keeps working on a new PHP minor (8.6 today) instead of refusing to start. New syntax of that version is
not understood until PHP-CS-Fixer supports it; CI runs the style check on the main PHP version.

Known PHP-CS-Fixer issue: in a script that starts with a shebang (`#!/usr/bin/env php`), `fully_qualified_strict_types` can
insert a new `use` above `declare(strict_types=1)`, which PHP rejects. Put such imports below the `declare` yourself; PHPStan
reports the broken order (`declareStrictTypes.notFirst`).

## Composer scripts convention

Every eSoul PHP repository defines these scripts, and the shared Jenkins `phpPackagePipeline` step calls them:

```json
{
    "scripts": {
        "phpstan": "phpstan analyse --memory-limit=1G",
        "cs": "php-cs-fixer fix --dry-run --diff",
        "cs:fix": "php-cs-fixer fix",
        "test": "phpunit"
    }
}
```

`cs` only reports (non-zero exit on violations); `cs:fix` rewrites the files.

## Development

```bash
composer update
composer phpstan
composer cs
composer test
```

The tests run the real `php-cs-fixer` binary through `Config::create()` against a badly formatted file and assert the fixed output.
CI (`Jenkinsfile`, `phpPackagePipeline`) runs the checks on PHP 8.5 and the tests on 8.4 and 8.6 after Composer re-resolved the
dependencies on that version. The package supports PHP 8.4 and every later 8.x release (`^8.4`).
