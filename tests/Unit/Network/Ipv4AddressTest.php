<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidIpv4Exception;
use MSSTC4PHP\ValueObject\Network\Cidr;
use MSSTC4PHP\ValueObject\Network\Ipv4Address;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testPrivateAndPublic(): void
    {
        self::assertTrue(Ipv4Address::from('10.0.0.1')->isPrivate());
        self::assertTrue(Ipv4Address::from('172.16.0.1')->isPrivate());
        self::assertTrue(Ipv4Address::from('192.168.1.1')->isPrivate());
        self::assertFalse(Ipv4Address::from('8.8.8.8')->isPrivate());

        self::assertTrue(Ipv4Address::from('8.8.8.8')->isPublic());
        self::assertFalse(Ipv4Address::from('10.0.0.1')->isPublic());
    }

    public function testLoopbackLinkLocalMulticast(): void
    {
        self::assertTrue(Ipv4Address::from('127.0.0.1')->isLoopback());
        self::assertFalse(Ipv4Address::from('8.8.8.8')->isLoopback());
        self::assertTrue(Ipv4Address::from('169.254.1.1')->isLinkLocal());
        self::assertTrue(Ipv4Address::from('224.0.0.1')->isMulticast());
    }

    public function testPrivateRangeBoundaries(): void
    {
        self::assertTrue(Ipv4Address::from('172.16.0.0')->isPrivate());
        self::assertTrue(Ipv4Address::from('172.31.255.255')->isPrivate());
        self::assertFalse(Ipv4Address::from('172.15.255.255')->isPrivate());
        self::assertFalse(Ipv4Address::from('172.32.0.0')->isPrivate());
    }

    public function testIsInRange(): void
    {
        self::assertTrue(Ipv4Address::from('192.168.1.42')->isInRange(Cidr::from('192.168.1.0/24')));
        self::assertFalse(Ipv4Address::from('192.168.2.1')->isInRange(Cidr::from('192.168.1.0/24')));
    }

    #[DataProvider('reservedProvider')]
    public function testReservedRangesAreNotPublic(string $address): void
    {
        self::assertTrue(Ipv4Address::from($address)->isReserved());
        self::assertFalse(Ipv4Address::from($address)->isPublic());
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function reservedProvider(): iterable
    {
        yield 'this host' => ['0.0.0.0'];
        yield 'cgnat' => ['100.64.0.1'];
        yield 'protocol assignments' => ['192.0.0.1'];
        yield 'documentation test-net-1' => ['192.0.2.5'];
        yield 'benchmarking' => ['198.18.0.1'];
        yield 'reserved future use' => ['240.0.0.1'];
        yield 'limited broadcast' => ['255.255.255.255'];
    }

    #[DataProvider('routableProvider')]
    public function testRoutableAddressesStayPublic(string $address): void
    {
        self::assertFalse(Ipv4Address::from($address)->isReserved());
        self::assertTrue(Ipv4Address::from($address)->isPublic());
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function routableProvider(): iterable
    {
        yield 'google dns' => ['8.8.8.8'];
        yield 'cloudflare dns' => ['1.1.1.1'];
        yield 'just below cgnat' => ['100.63.255.255'];
        yield 'just above benchmarking' => ['198.20.0.0'];
    }
}
