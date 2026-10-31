<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\I18n;

use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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
        // The four cases below are exactly where CLDR display digits and ISO 4217
        // minor units disagree; picking USD/JPY alone hides the whole class.
        yield 'IQD' => ['IQD', 3];
        yield 'CLF' => ['CLF', 4];
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
    }

    #[DataProvider('displayFractionDigitsProvider')]
    public function testDisplayFractionDigits(string $code, int $expected): void
    {
        self::assertSame($expected, CurrencyCode::from($code)->displayFractionDigits());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function historicCodeProvider(): iterable
    {
        yield 'ADP' => ['ADP'];
        yield 'BEF' => ['BEF'];
        yield 'ATS' => ['ATS'];
    }

    #[DataProvider('historicCodeProvider')]
    public function testHistoricCodesHaveNoIsoMinorUnits(string $code): void
    {
        self::assertNull(CurrencyCode::from($code)->isoMinorUnits());
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
