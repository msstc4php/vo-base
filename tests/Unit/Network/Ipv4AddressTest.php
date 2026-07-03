<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidIpv4Exception;
use MSSTC4PHP\ValueObject\Network\Ipv4Address;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Ipv4Address::class)]
final class Ipv4AddressTest extends TestCase
{
    public function testConstructsFromValidAddress(): void
    {
        self::assertSame('192.168.0.1', Ipv4Address::from('192.168.0.1')->value);
    }

    public function testOutOfRangeOctetThrows(): void
    {
        $this->expectException(InvalidIpv4Exception::class);

        Ipv4Address::from('256.0.0.1');
    }

    public function testIncompleteAddressThrows(): void
    {
        $this->expectException(InvalidIpv4Exception::class);

        Ipv4Address::from('1.2.3');
    }

    public function testEmptyStringThrows(): void
    {
        $this->expectException(InvalidIpv4Exception::class);

        Ipv4Address::from('');
    }
}
