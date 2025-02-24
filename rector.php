<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withBootstrapFiles([
        __DIR__ . '/vendor/bin/.phpunit/phpunit/vendor/autoload.php',
        __DIR__ . '/vendor/autoload.php',
    ])
    ->withSets([LevelSetList::UP_TO_PHP_74])
;
