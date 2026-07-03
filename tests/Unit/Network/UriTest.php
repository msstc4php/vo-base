<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidUriException;
use MSSTC4PHP\ValueObject\Network\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
    public function testParsesComponents(): void
    {
        $uri = Uri::from('https://u:p@host:8080/p?q=1#f');

        self::assertSame('https://u:p@host:8080/p?q=1#f', $uri->value);
        self::assertSame('https', $uri->scheme());
        self::assertSame('host', $uri->host());
        self::assertSame(8080, $uri->port());
        self::assertSame('/p', $uri->path());
        self::assertSame('q=1', $uri->query());
        self::assertSame('f', $uri->fragment());
    }

    public function testMissingSchemeThrows(): void
    {
        $this->expectException(InvalidUriException::class);

        Uri::from('/no-scheme');
    }

    public function testOptionalComponentsAreNullWhenAbsent(): void
    {
        $uri = Uri::from('mailto:user@example.com');

        self::assertNull($uri->host());
        self::assertNull($uri->port());
        self::assertNull($uri->query());
        self::assertNull($uri->fragment());
    }

    public function testPathIsEmptyStringWhenAbsent(): void
    {
        self::assertSame('', Uri::from('https://example.com')->path());
    }

    public function testTryFromAndIsValid(): void
    {
        self::assertNull(Uri::tryFrom('/no-scheme'));
        self::assertTrue(Uri::isValid('https://example.com'));
    }
}
