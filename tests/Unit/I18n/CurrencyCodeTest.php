<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\I18n;

use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Currencies;
use ValueError;

#[CoversClass(CurrencyCode::class)]
final class CurrencyCodeTest extends TestCase
{
    public function testFrom(): void
    {
        self::assertSame('USD', CurrencyCode::from('USD')->value);
    }

    public function testUnknownCodeThrows(): void
    {
        $this->expectException(ValueError::class);

        CurrencyCode::from('ZZZ');
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function isoMinorUnitsProvider(): iterable
    {
        yield 'USD' => ['USD', 2];
        yield 'JPY' => ['JPY', 0];
        yield 'CLF' => ['CLF', 4];
        // IQD, HUF, IDR and COP are where CLDR display digits and ISO 4217 minor
        // units disagree; picking USD/JPY alone hides the whole class.
        yield 'IQD' => ['IQD', 3];
        yield 'HUF' => ['HUF', 2];
        yield 'IDR' => ['IDR', 2];
        yield 'COP' => ['COP', 2];
    }

    #[DataProvider('isoMinorUnitsProvider')]
    public function testIsoMinorUnits(string $code, int $expected): void
    {
        self::assertSame($expected, CurrencyCode::from($code)->isoMinorUnits());
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function displayFractionDigitsProvider(): iterable
    {
        yield 'USD' => ['USD', 2];
        yield 'JPY' => ['JPY', 0];
        yield 'HUF' => ['HUF', 0];
        yield 'IQD' => ['IQD', 0];
        yield 'CLF' => ['CLF', 4];
        // Pins the CLDR change that came with symfony/intl 7.4.22 (0 before).
        yield 'PKR' => ['PKR', 2];
    }

    #[DataProvider('displayFractionDigitsProvider')]
    public function testDisplayFractionDigits(string $code, int $expected): void
    {
        self::assertSame($expected, CurrencyCode::from($code)->displayFractionDigits());
    }

    /**
     * @return iterable<string, array{string, int<0, max>}>
     */
    public static function withdrawnCodeProvider(): iterable
    {
        yield 'ADP' => ['ADP', 0];
        yield 'BEF' => ['BEF', 0];
        yield 'ATS' => ['ATS', 2];
    }

    /**
     * @param int<0, max> $expected
     */
    #[DataProvider('withdrawnCodeProvider')]
    public function testWithdrawnCodesKeepTheirIsoMinorUnits(string $code, int $expected): void
    {
        self::assertSame($expected, CurrencyCode::from($code)->isoMinorUnits());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function codeWithoutIsoMinorUnitsProvider(): iterable
    {
        yield 'CNH' => ['CNH'];
        yield 'XEU' => ['XEU'];
    }

    #[DataProvider('codeWithoutIsoMinorUnitsProvider')]
    public function testCodeWithoutIsoMinorUnitsReturnsNull(string $code): void
    {
        self::assertNull(CurrencyCode::from($code)->isoMinorUnits());
    }

    public function testCodeUnknownToCldrFallsBackToIsoMinorUnitsForDisplay(): void
    {
        if (\class_exists(Currencies::class)) {
            self::assertFalse(Currencies::exists('XAD'), 'CLDR knows XAD now: pick another ISO-only code for this test.');
        }

        self::assertSame(2, CurrencyCode::XAD->displayFractionDigits());
        self::assertSame(2, CurrencyCode::XAD->isoMinorUnits());
    }

    public function testEveryCaseAnswersBothMethodsAndRoundTrips(): void
    {
        foreach (CurrencyCode::cases() as $currency) {
            self::assertGreaterThanOrEqual(0, $currency->displayFractionDigits(), $currency->value);

            $iso = $currency->isoMinorUnits();
            if ($iso !== null) {
                self::assertGreaterThanOrEqual(0, $iso, $currency->value);
            }

            self::assertSame($currency, CurrencyCode::from($currency->value), $currency->value);
        }
    }

    /**
     * Pins the full code-to-minor-units map, not just the set of null codes. A
     * coordinated bump of symfony/intl and brick/money can silently change the
     * digit count of a supported currency, which shifts every already-stored
     * minor-unit amount by an order of magnitude. Comparing the two source
     * tables cannot see that -- they move together.
     */
    public function testIsoMinorUnitsMapMatchesSnapshot(): void
    {
        $snapshot = \json_decode(
            (string) \file_get_contents(__DIR__ . '/iso-minor-units.snapshot.json'),
            true,
            512,
            \JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($snapshot);

        $actual = [];
        foreach (CurrencyCode::cases() as $currency) {
            $actual[$currency->value] = $currency->isoMinorUnits();
        }
        \ksort($actual);

        self::assertSame(
            $snapshot,
            $actual,
            'ISO minor units changed. Decide whether already-stored amounts need recomputing, then update the snapshot.',
        );
    }
}
