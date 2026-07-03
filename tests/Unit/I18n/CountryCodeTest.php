<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\I18n;

use MSSTC4PHP\ValueObject\Exception\InvalidCountryCodeException;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ValueError;

#[CoversClass(CountryCode::class)]
final class CountryCodeTest extends TestCase
{
    public function testFromAlpha2AndAccessors(): void
    {
        $us = CountryCode::from('US');

        self::assertSame('US', $us->value);
        self::assertSame('USA', $us->alpha3());
        self::assertSame('840', $us->numeric());
    }

    public function testUnknownAlpha2Throws(): void
    {
        $this->expectException(ValueError::class);

        CountryCode::from('XX');
    }

    public function testFromAlpha3RoundTrips(): void
    {
        self::assertSame(CountryCode::US, CountryCode::fromAlpha3('USA'));
        self::assertSame(CountryCode::US, CountryCode::fromAlpha3('usa'));
    }

    public function testFromNumericRoundTrips(): void
    {
        self::assertSame(CountryCode::US, CountryCode::fromNumeric('840'));
        // Zero-padding: '4' resolves the same as '004'.
        self::assertSame(CountryCode::fromNumeric('004'), CountryCode::fromNumeric('4'));
    }

    public function testUnknownAlpha3Throws(): void
    {
        $this->expectException(InvalidCountryCodeException::class);

        CountryCode::fromAlpha3('ZZZ');
    }

    public function testEveryCaseHasConsistentAccessorsAndRoundTrips(): void
    {
        foreach (CountryCode::cases() as $country) {
            self::assertSame(3, \strlen($country->alpha3()), $country->value);
            self::assertMatchesRegularExpression('/^\d{3}$/', $country->numeric(), $country->value);
            self::assertSame($country, CountryCode::fromAlpha3($country->alpha3()), $country->value);
            self::assertSame($country, CountryCode::fromNumeric($country->numeric()), $country->value);
        }
    }
}
