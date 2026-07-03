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
}
