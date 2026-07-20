<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidIpv6Exception;
use MSSTC4PHP\ValueObject\Network\Cidr;
use MSSTC4PHP\ValueObject\Network\Ipv6Address;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testClassification(): void
    {
        self::assertTrue(Ipv6Address::from('::1')->isLoopback());
        self::assertTrue(Ipv6Address::from('fe80::1')->isLinkLocal());
        self::assertTrue(Ipv6Address::from('fc00::1')->isPrivate());
        self::assertTrue(Ipv6Address::from('fd12::1')->isPrivate());
        self::assertTrue(Ipv6Address::from('ff02::1')->isMulticast());

        // A genuinely routable global-unicast address (Cloudflare).
        self::assertTrue(Ipv6Address::from('2606:4700::1')->isPublic());
        self::assertFalse(Ipv6Address::from('::1')->isPublic());
    }

    #[DataProvider('reservedProvider')]
    public function testReservedRangesAreNotPublic(string $address): void
    {
        self::assertTrue(Ipv6Address::from($address)->isReserved());
        self::assertFalse(Ipv6Address::from($address)->isPublic());
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function reservedProvider(): iterable
    {
        yield 'unspecified' => ['::'];
        yield 'discard-only' => ['100::1'];
        yield 'v4-mapped' => ['::ffff:192.0.2.1'];
        yield 'v4/v6 translation' => ['64:ff9b::1'];
        yield 'benchmarking' => ['2001:2::1'];
        yield 'documentation 2001:db8' => ['2001:db8::1'];
        yield 'documentation 3fff' => ['3fff::1'];
    }

    #[DataProvider('routableProvider')]
    public function testRoutableAddressesStayPublic(string $address): void
    {
        self::assertFalse(Ipv6Address::from($address)->isReserved());
        self::assertTrue(Ipv6Address::from($address)->isPublic());
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function routableProvider(): iterable
    {
        yield 'cloudflare' => ['2606:4700::1'];
        yield 'google' => ['2001:4860:4860::8888'];
        yield 'just outside 2001:db8' => ['2001:db9::1'];
        yield 'just outside 3fff' => ['4000::1'];
    }

    public function testIsInRange(): void
    {
        self::assertTrue(Ipv6Address::from('2001:db8::1')->isInRange(Cidr::from('2001:db8::/32')));
        self::assertFalse(Ipv6Address::from('2001:db9::1')->isInRange(Cidr::from('2001:db8::/32')));
    }
}
