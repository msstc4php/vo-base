<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\I18n;

use MSSTC4PHP\ValueObject\Tests\Support\IsoEnumGenerator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guards against ISO enum drift: the committed files must equal fresh generator output.
 * Regenerate with `php tools/generate-iso-enums.php` when symfony/intl updates.
 */
#[CoversNothing]
final class IsoEnumsUpToDateTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function generatedFiles(): iterable
    {
        foreach (new IsoEnumGenerator()->all() as $relativePath => $content) {
            yield $relativePath => [$relativePath, $content];
        }
    }

    #[DataProvider('generatedFiles')]
    public function testCommittedFileMatchesGenerator(string $relativePath, string $expected): void
    {
        $committed = \file_get_contents(\dirname(__DIR__, 3) . '/' . $relativePath);

        self::assertSame($expected, $committed, \sprintf('%s is stale — run php tools/generate-iso-enums.php', $relativePath));
    }
}
