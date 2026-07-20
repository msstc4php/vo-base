<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidCidrException;
use MSSTC4PHP\ValueObject\Network\Cidr;
use MSSTC4PHP\ValueObject\Network\IpAddress;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RangeException;

#[CoversClass(Cidr::class)]
final class CidrTest extends TestCase
{
    public function testParsesIpv4Block(): void
    {
        $cidr = Cidr::from('192.168.0.0/24');

        self::assertSame(4, $cidr->version());
        self::assertSame(24, $cidr->prefixLength());
        self::assertTrue($cidr->address()->equals(IpAddress::fromString('192.168.0.0')));
    }

    public function testParsesIpv6Block(): void
    {
        $cidr = Cidr::from('2001:db8::/32');

        self::assertSame(6, $cidr->version());
        self::assertSame(32, $cidr->prefixLength());
    }

    public function testFromStringNamedConstructor(): void
    {
        self::assertSame('192.168.0.0/24', Cidr::fromString('192.168.0.0/24')->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'ipv4 prefix out of range' => ['192.168.0.0/33'];
        yield 'ipv6 prefix out of range' => ['2001:db8::/129'];
        yield 'not an ip' => ['notanip/24'];
        yield 'missing prefix' => ['10.0.0.0'];
        yield 'non-integer prefix' => ['10.0.0.0/x'];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalid(string $value): void
    {
        $this->expectException(InvalidCidrException::class);

        Cidr::from($value);
    }

    public function testContainsIpv4(): void
    {
        $block = Cidr::from('192.168.1.0/24');

        self::assertTrue($block->contains(IpAddress::from('192.168.1.42')));
        self::assertTrue($block->contains(IpAddress::from('192.168.1.0')));
        self::assertTrue($block->contains(IpAddress::from('192.168.1.255')));
        self::assertFalse($block->contains(IpAddress::from('192.168.2.1')));
    }

    public function testContainsIpv6(): void
    {
        $block = Cidr::from('2001:db8::/32');

        self::assertTrue($block->contains(IpAddress::from('2001:db8::1')));
        self::assertFalse($block->contains(IpAddress::from('2001:db9::1')));
    }

    public function testContainsRejectsOtherFamily(): void
    {
        self::assertFalse(Cidr::from('192.168.1.0/24')->contains(IpAddress::from('2001:db8::1')));
        self::assertFalse(Cidr::from('2001:db8::/32')->contains(IpAddress::from('192.168.1.1')));
    }

    public function testIpv4AddressRange(): void
    {
        $block = Cidr::from('192.168.1.0/24');

        self::assertSame('192.168.1.0', $block->firstAddress()->value);
        self::assertSame('192.168.1.255', $block->lastAddress()->value);
        self::assertSame('255.255.255.0', $block->netmask()->value);
        self::assertSame('192.168.1.255', $block->broadcast()?->value);
        self::assertSame(254, $block->hostCount());
    }

    public function testNormalizesToNetworkAddress(): void
    {
        // Host bits set on input are cleared when computing the network address.
        $block = Cidr::from('192.168.1.42/24');

        self::assertSame('192.168.1.0', $block->firstAddress()->value);
    }

    public function testIpv6AddressRange(): void
    {
        $block = Cidr::from('2001:db8::/32');

        self::assertSame('2001:db8::', $block->firstAddress()->value);
        self::assertSame('2001:db8:ffff:ffff:ffff:ffff:ffff:ffff', $block->lastAddress()->value);
        self::assertSame('ffff:ffff::', $block->netmask()->value);
        self::assertNull($block->broadcast());
    }

    public function testSingleHostAndFullRangeCounts(): void
    {
        self::assertSame(1, Cidr::from('192.168.1.7/32')->hostCount());
        self::assertSame(2, Cidr::from('192.168.1.0/31')->hostCount());
        self::assertSame(1, Cidr::from('2001:db8::/128')->hostCount());
    }

    public function testHostCountThrowsOnWideIpv6(): void
    {
        $this->expectException(RangeException::class);

        Cidr::from('2001:db8::/32')->hostCount();
    }

    public function testOverlaps(): void
    {
        self::assertTrue(Cidr::from('192.168.0.0/16')->overlaps(Cidr::from('192.168.1.0/24')));
        self::assertTrue(Cidr::from('192.168.1.0/24')->overlaps(Cidr::from('192.168.0.0/16')));
        self::assertFalse(Cidr::from('10.0.0.0/8')->overlaps(Cidr::from('192.168.0.0/16')));
        self::assertFalse(Cidr::from('192.168.0.0/16')->overlaps(Cidr::from('2001:db8::/32')));
    }

    public function testOverlapsAtRangeBoundary(): void
    {
        // The single host equals the block's last address -> the ranges touch.
        self::assertTrue(Cidr::from('192.168.1.0/24')->overlaps(Cidr::from('192.168.1.255/32')));
        self::assertTrue(Cidr::from('192.168.1.255/32')->overlaps(Cidr::from('192.168.1.0/24')));
        // Adjacent but non-overlapping blocks.
        self::assertFalse(Cidr::from('192.168.0.0/24')->overlaps(Cidr::from('192.168.1.0/24')));
    }

    public function testIsSubnetOf(): void
    {
        self::assertTrue(Cidr::from('192.168.1.0/24')->isSubnetOf(Cidr::from('192.168.0.0/16')));
        self::assertTrue(Cidr::from('192.168.0.0/16')->isSubnetOf(Cidr::from('192.168.0.0/16')));
        self::assertFalse(Cidr::from('192.168.0.0/16')->isSubnetOf(Cidr::from('192.168.1.0/24')));
        self::assertFalse(Cidr::from('192.168.1.0/24')->isSubnetOf(Cidr::from('2001:db8::/32')));
        // Shares the network address but is wider -> not a subnet.
        self::assertFalse(Cidr::from('10.0.0.0/8')->isSubnetOf(Cidr::from('10.0.0.0/16')));
    }

    /**
     * Invariant: a block always contains its own first and last address, across
     * v4/v6 prefix widths.
     *
     * @param non-empty-string $block
     */
    #[DataProvider('blockProvider')]
    public function testFirstAndLastAreAlwaysContained(string $block): void
    {
        $cidr = Cidr::from($block);

        self::assertTrue($cidr->contains($cidr->firstAddress()), 'first not contained');
        self::assertTrue($cidr->contains($cidr->lastAddress()), 'last not contained');
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function blockProvider(): iterable
    {
        yield 'v4 /0' => ['0.0.0.0/0'];
        yield 'v4 /24' => ['192.168.1.0/24'];
        yield 'v4 /31' => ['192.168.1.0/31'];
        yield 'v4 /32' => ['192.168.1.7/32'];
        yield 'v6 /64' => ['2001:db8::/64'];
        yield 'v6 /128' => ['2001:db8::1/128'];
        yield 'v6 /0' => ['::/0'];
    }
}
