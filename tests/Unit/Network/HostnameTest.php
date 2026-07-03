<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidHostnameException;
use MSSTC4PHP\ValueObject\Network\Hostname;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Hostname::class)]
final class HostnameTest extends TestCase
{
    public function testConstructsAndLowercases(): void
    {
        self::assertSame('a.example.com', Hostname::from('A.EXAMPLE.COM')->value);
    }

    public function testLabelLongerThan63CharsThrows(): void
    {
        $this->expectException(InvalidHostnameException::class);

        Hostname::from(\str_repeat('a', 64) . '.com');
    }

    public function testLeadingHyphenThrows(): void
    {
        $this->expectException(InvalidHostnameException::class);

        Hostname::from('-host');
    }

    public function testTrailingDotThrows(): void
    {
        $this->expectException(InvalidHostnameException::class);

        Hostname::from('a.');
    }

    public function testEmptyValueThrows(): void
    {
        $this->expectException(InvalidHostnameException::class);

        Hostname::from('');
    }

    public function testOverLongValueThrows(): void
    {
        $this->expectException(InvalidHostnameException::class);

        Hostname::from(\str_repeat('a.', 200) . 'com');
    }

    public function testTryFromIsValidEqualsAndSerialization(): void
    {
        self::assertNull(Hostname::tryFrom('-bad'));
        self::assertTrue(Hostname::isValid('example.com'));
        self::assertTrue(Hostname::from('example.com')->equals(Hostname::from('EXAMPLE.COM')));
        self::assertFalse(Hostname::from('a.com')->equals(Hostname::from('b.com')));
        self::assertSame('example.com', Hostname::from('example.com')->jsonSerialize());
        self::assertSame('example.com', (string) Hostname::from('example.com'));
    }
}
