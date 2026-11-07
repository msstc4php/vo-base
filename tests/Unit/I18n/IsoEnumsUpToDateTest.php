<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\I18n;

use MSSTC4PHP\ValueObject\Tests\Support\IsoEnumGenerator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Countries;

/**
 * Guards against ISO enum drift: the committed files must equal fresh generator output.
 * Regenerate with `php tools/generate-iso-enums.php` when symfony/intl updates.
 */
#[CoversNothing]
#[RequiresMethod(Countries::class, 'getCountryCodes')]
final class IsoEnumsUpToDateTest extends TestCase
{
    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function generatedFiles(): iterable
    {
        foreach (['CountryCode', 'CurrencyCode', 'LanguageCode'] as $enum) {
            yield $enum => ['src/I18n/' . $enum . '.php'];
        }
    }

    public function testEveryGeneratedFileIsGuarded(): void
    {
        self::assertSame(
            \array_column(\iterator_to_array(self::generatedFiles(), false), 0),
            \array_keys(new IsoEnumGenerator()->all()),
        );
    }

    #[DataProvider('generatedFiles')]
    public function testCommittedFileMatchesGenerator(string $relativePath): void
    {
        $expected = new IsoEnumGenerator()->all()[$relativePath];
        $committed = \file_get_contents(\dirname(__DIR__, 3) . '/' . $relativePath);

        self::assertSame($expected, $committed, \sprintf('%s is stale — run php tools/generate-iso-enums.php', $relativePath));
    }
}
