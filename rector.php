<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withPhpSets(php84: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, codingStyle: true, typeDeclarations: true)
    ->withSets([LevelSetList::UP_TO_PHP_84])
    ->withSkip([
        // Generated ISO enums are owned by tools/generate-iso-enums.php.
        __DIR__ . '/src/I18n/CountryCode.php',
        __DIR__ . '/src/I18n/CurrencyCode.php',
        __DIR__ . '/src/I18n/LanguageCode.php',
    ]);
