<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidIpAddressException;
use MSSTC4PHP\ValueObject\Network\IpAddress;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IpAddress::class)]
final class IpAddressTest extends TestCase
{
    public function testDetectsVersionFour(): void
    {
        self::assertSame(4, IpAddress::from('192.168.0.1')->version());
    }

    public function testDetectsVersionSix(): void
    {
        self::assertSame(6, IpAddress::from('::1')->version());
    }

    public function testGarbageThrows(): void
    {
        $this->expectException(InvalidIpAddressException::class);

        IpAddress::from('not-an-ip');
    }

    public function testFromStringNamedConstructor(): void
    {
        self::assertSame('192.168.0.1', IpAddress::fromString('192.168.0.1')->value);
    }
}
