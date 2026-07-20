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

    public function testWithComponentsRoundTrip(): void
    {
        $uri = Uri::from('https://example.com/a?x=1#top');

        self::assertSame('https://example.com/b?x=1#top', $uri->withPath('/b')->value);
        self::assertSame('https://example.com/a?y=2#top', $uri->withQuery('y=2')->value);
        self::assertSame('https://example.com/a?x=1', $uri->withFragment('')->value);
    }

    public function testQueryParameters(): void
    {
        self::assertSame(['x' => '1', 'y' => '2'], Uri::from('https://example.com/a?x=1&y=2')->queryParameters());
        self::assertSame([], Uri::from('mailto:user@example.com')->queryParameters());
    }

    public function testWithSchemeOnSchemeOnlyUri(): void
    {
        self::assertSame('urn:isbn:0451450523', Uri::from('urn:isbn:0451450523')->withScheme('urn')->value);
    }
}
