<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidIpv6Exception;
use MSSTC4PHP\ValueObject\Network\Ipv6Address;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Ipv6Address::class)]
final class Ipv6AddressTest extends TestCase
{
    public function testConstructsFromLoopback(): void
    {
        self::assertSame('::1', Ipv6Address::from('::1')->value);
    }

    public function testConstructsFromDocumentationAddress(): void
    {
        self::assertSame('2001:db8::1', Ipv6Address::from('2001:db8::1')->value);
    }

    public function testIpv4StringThrows(): void
    {
        $this->expectException(InvalidIpv6Exception::class);

        Ipv6Address::from('192.168.0.1');
    }
}
