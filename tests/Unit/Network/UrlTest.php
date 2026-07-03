<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidUrlException;
use MSSTC4PHP\ValueObject\Network\Hostname;
use MSSTC4PHP\ValueObject\Network\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Url::class)]
final class UrlTest extends TestCase
{
    public function testConstructsFromValidUrl(): void
    {
        $url = Url::from('https://example.com/a');

        self::assertSame('https://example.com/a', $url->value);
        self::assertSame('https', $url->scheme());
        self::assertSame('/a', $url->path());
        self::assertTrue($url->host()->equals(Hostname::from('example.com')));
    }

    public function testNonHttpSchemeThrows(): void
    {
        $this->expectException(InvalidUrlException::class);

        Url::from('mailto:x@y');
    }

    public function testMissingHostThrows(): void
    {
        $this->expectException(InvalidUrlException::class);

        Url::from('http://');
    }

    public function testPortIsExposedWhenPresent(): void
    {
        self::assertSame(8443, Url::from('https://example.com:8443/a')->port());
    }

    public function testPortIsNullWhenAbsent(): void
    {
        self::assertNull(Url::from('https://example.com/a')->port());
    }

    public function testPathIsEmptyStringWhenAbsent(): void
    {
        self::assertSame('', Url::from('https://example.com')->path());
    }

    public function testTryFromAndIsValid(): void
    {
        self::assertNull(Url::tryFrom('not a url'));
        self::assertFalse(Url::isValid('ftp://example.com'));
        self::assertTrue(Url::isValid('https://example.com'));
    }

    public function testJsonSerialization(): void
    {
        self::assertSame('https://example.com/a', Url::from('https://example.com/a')->jsonSerialize());
    }
}
