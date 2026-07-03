<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\I18n;

use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\LanguageCode;
use MSSTC4PHP\ValueObject\I18n\Locale;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Locale::class)]
final class LocaleTest extends TestCase
{
    public function testLanguageAndRegion(): void
    {
        $locale = Locale::from('en-US');

        self::assertSame('en-US', $locale->value);
        self::assertSame(LanguageCode::EN, $locale->language());
        self::assertSame(CountryCode::US, $locale->region());
    }

    public function testLanguageOnly(): void
    {
        $locale = Locale::from('en');

        self::assertSame('en', $locale->value);
        self::assertNull($locale->region());
    }

    public function testNormalizesCase(): void
    {
        self::assertSame('en-US', Locale::from('EN-us')->value);
    }

    public function testWrongSeparatorThrows(): void
    {
        self::assertNull(Locale::tryFrom('en_US'));
    }

    public function testUnknownLanguageThrows(): void
    {
        self::assertNull(Locale::tryFrom('zz-US'));
    }
}
