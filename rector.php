<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\EarlyReturn\Rector\Return_\ReturnBinaryOrToEarlyReturnRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\AddSeeTestAnnotationRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withoutParallel()
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        rectorPreset: true,
    )
    ->withImportNames(removeUnusedImports: true)
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        ChangeOrIfContinueToMultiContinueRector::class,
        ReturnBinaryOrToEarlyReturnRector::class,
        PostIncDecToPreIncDecRector::class,
        DisallowedEmptyRuleFixerRector::class,
        NewlineAfterStatementRector::class,
        CatchExceptionNameMatchingTypeRector::class,
        AddSeeTestAnnotationRector::class,
        // Generated ISO enums are owned by tools/generate-iso-enums.php.
        __DIR__ . '/src/I18n/CountryCode.php',
        __DIR__ . '/src/I18n/CurrencyCode.php',
        __DIR__ . '/src/I18n/LanguageCode.php',
    ]);
