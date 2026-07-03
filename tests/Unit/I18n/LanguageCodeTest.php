<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\I18n;

use MSSTC4PHP\ValueObject\I18n\LanguageCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LanguageCode::class)]
final class LanguageCodeTest extends TestCase
{
    public function testFromKnownCode(): void
    {
        self::assertSame('en', LanguageCode::from('en')->value);
    }

    /**
     * @return iterable<array{string}>
     */
    public static function unknownCodes(): iterable
    {
        yield ['zz'];
        yield ['q1'];
    }

    #[DataProvider('unknownCodes')]
    public function testUnknownCodeReturnsNull(string $code): void
    {
        self::assertNull(LanguageCode::tryFrom($code));
    }
}
