<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidIpAddressException;
use MSSTC4PHP\ValueObject\Network\Cidr;
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

    public function testDelegatesClassificationToConcreteFamily(): void
    {
        self::assertTrue(IpAddress::from('10.0.0.1')->isPrivate());
        self::assertTrue(IpAddress::from('127.0.0.1')->isLoopback());
        self::assertTrue(IpAddress::from('8.8.8.8')->isPublic());
        self::assertTrue(IpAddress::from('240.0.0.1')->isReserved());
        self::assertTrue(IpAddress::from('2001:db8::1')->isReserved());
        self::assertTrue(IpAddress::from('::1')->isLoopback());
        self::assertTrue(IpAddress::from('fe80::1')->isLinkLocal());
        self::assertTrue(IpAddress::from('ff02::1')->isMulticast());
    }

    public function testIsInRange(): void
    {
        self::assertTrue(IpAddress::from('192.168.1.5')->isInRange(Cidr::from('192.168.1.0/24')));
        self::assertFalse(IpAddress::from('10.0.0.1')->isInRange(Cidr::from('192.168.1.0/24')));
    }
}
