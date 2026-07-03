<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidUrlSchemeException;
use MSSTC4PHP\ValueObject\Network\UrlScheme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlScheme::class)]
final class UrlSchemeTest extends TestCase
{
    public function testConstructsFromValidScheme(): void
    {
        self::assertSame('http', UrlScheme::from('http')->value);
        self::assertSame('ftp', UrlScheme::from('ftp')->value);
    }

    public function testNormalizesToLowercase(): void
    {
        self::assertSame('https', UrlScheme::from('HTTPS')->value);
    }

    public function testSchemeStartingWithDigitThrows(): void
    {
        $this->expectException(InvalidUrlSchemeException::class);

        UrlScheme::from('1abc');
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(InvalidUrlSchemeException::class);

        UrlScheme::from('');
    }
}
