<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\I18n;

use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ValueError;

#[CoversClass(CurrencyCode::class)]
final class CurrencyCodeTest extends TestCase
{
    public function testFromAndMinorUnits(): void
    {
        self::assertSame('USD', CurrencyCode::from('USD')->value);
        self::assertSame(2, CurrencyCode::from('USD')->minorUnits());
        self::assertSame(0, CurrencyCode::from('JPY')->minorUnits());
    }

    public function testUnknownCodeThrows(): void
    {
        $this->expectException(ValueError::class);

        CurrencyCode::from('ZZZ');
    }

    public function testEveryCaseHasNonNegativeMinorUnitsAndRoundTrips(): void
    {
        foreach (CurrencyCode::cases() as $currency) {
            self::assertGreaterThanOrEqual(0, $currency->minorUnits(), $currency->value);
            self::assertSame($currency, CurrencyCode::from($currency->value), $currency->value);
        }
    }
}
