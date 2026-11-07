<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidIpRangeException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Network\Cidr;
use MSSTC4PHP\ValueObject\Network\IpAddress;
use MSSTC4PHP\ValueObject\Network\IpRange;
use MSSTC4PHP\ValueObject\Support\PackedAddress;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(InvalidValueObjectException::class)]
#[CoversClass(IpRange::class)]
#[CoversClass(PackedAddress::class)]
#[UsesClass(IpAddress::class)]
#[UsesClass(Cidr::class)]
final class IpRangeTest extends TestCase
{
    public function testParsesIpv4Range(): void
    {
        $range = IpRange::fromString('10.0.0.5-10.0.0.20');

        self::assertSame(4, $range->version());
        self::assertSame('10.0.0.5', $range->firstAddress()->value);
        self::assertSame('10.0.0.20', $range->lastAddress()->value);
    }

    public function testCanonicalizesIpv6Bounds(): void
    {
        $range = IpRange::from('2001:DB8:0:0::1-2001:db8::00ff');

        self::assertSame('2001:db8::1-2001:db8::ff', $range->value);
        self::assertSame(6, $range->version());
    }

    public function testSingleAddressRange(): void
    {
        $range = IpRange::from('192.0.2.1-192.0.2.1');

        self::assertSame('1', $range->addressCount());
        self::assertTrue($range->contains(IpAddress::fromString('192.0.2.1')));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'no separator' => ['10.0.0.1'];
        yield 'three parts' => ['10.0.0.1-10.0.0.2-10.0.0.3'];
        yield 'empty bound' => ['10.0.0.1-'];
        yield 'not an ip' => ['10.0.0.1-nope'];
        yield 'mixed versions' => ['10.0.0.1-2001:db8::1'];
        yield 'reversed' => ['10.0.0.9-10.0.0.1'];
        yield 'spaces' => ['10.0.0.1 - 10.0.0.2'];
        yield 'trailing newline' => ["10.0.0.1-10.0.0.2\n"];
        yield 'zone id' => ['fe80::1%eth0-fe80::2'];
        yield 'leading zeros' => ['010.0.0.1-10.0.0.2'];
        yield 'ipv4 against ipv4-mapped' => ['1.2.3.4-::ffff:1.2.3.5'];
    }

    #[DataProvider('invalidProvider')]
    public function testRejectsInvalid(string $value): void
    {
        $this->expectException(InvalidIpRangeException::class);

        IpRange::from($value);
    }

    public function testFromBoundsAndFromCidr(): void
    {
        self::assertSame(
            '10.0.0.1-10.0.0.9',
            IpRange::fromBounds(IpAddress::fromString('10.0.0.1'), IpAddress::fromString('10.0.0.9'))->value,
        );
        self::assertSame('192.168.0.0-192.168.0.255', IpRange::fromCidr(Cidr::from('192.168.0.17/24'))->value);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function containsProvider(): iterable
    {
        yield 'first' => ['10.0.0.5', true];
        yield 'inside' => ['10.0.0.12', true];
        yield 'last' => ['10.0.0.20', true];
        yield 'below' => ['10.0.0.4', false];
        yield 'above' => ['10.0.0.21', false];
        yield 'other version' => ['::ffff:10.0.0.12', false];
    }

    #[DataProvider('containsProvider')]
    public function testContains(string $address, bool $expected): void
    {
        self::assertSame($expected, IpRange::from('10.0.0.5-10.0.0.20')->contains(IpAddress::fromString($address)));
    }

    /**
     * @return iterable<string, array{string, string, bool, bool}>
     */
    public static function relationProvider(): iterable
    {
        // [this, other, overlaps, this is a subrange of other]
        yield 'inside' => ['10.0.0.8-10.0.0.9', '10.0.0.5-10.0.0.20', true, true];
        yield 'equal' => ['10.0.0.5-10.0.0.20', '10.0.0.5-10.0.0.20', true, true];
        yield 'touching at one address' => ['10.0.0.1-10.0.0.5', '10.0.0.5-10.0.0.20', true, false];
        yield 'adjacent' => ['10.0.0.1-10.0.0.4', '10.0.0.5-10.0.0.20', false, false];
        yield 'covering' => ['10.0.0.0-10.0.0.255', '10.0.0.5-10.0.0.20', true, false];
        yield 'other version' => ['::-::ffff', '0.0.0.0-0.0.255.255', false, false];
    }

    #[DataProvider('relationProvider')]
    public function testOverlapsAndSubrange(string $subject, string $other, bool $overlaps, bool $subrange): void
    {
        $a = IpRange::from($subject);
        $b = IpRange::from($other);

        self::assertSame($overlaps, $a->overlaps($b));
        self::assertSame($overlaps, $b->overlaps($a));
        self::assertSame($subrange, $a->isSubrangeOf($b));
    }

    /**
     * @return iterable<string, array{string, numeric-string}>
     */
    public static function addressCountProvider(): iterable
    {
        yield 'ipv4 small' => ['10.0.0.5-10.0.0.20', '16'];
        yield 'ipv4 byte borrow' => ['10.0.0.255-10.0.1.0', '2'];
        yield 'ipv4 whole space' => ['0.0.0.0-255.255.255.255', '4294967296'];
        yield 'ipv6 /64' => ['2001:db8::-2001:db8::ffff:ffff:ffff:ffff', '18446744073709551616'];
        yield 'ipv6 whole space' => ['::-ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff', '340282366920938463463374607431768211456'];
    }

    #[DataProvider('addressCountProvider')]
    public function testAddressCountIsExact(string $range, string $expected): void
    {
        self::assertSame($expected, IpRange::from($range)->addressCount());
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function toCidrsProvider(): iterable
    {
        yield 'aligned block' => ['192.168.0.0-192.168.0.255', ['192.168.0.0/24']];
        yield 'single address' => ['10.0.0.7-10.0.0.7', ['10.0.0.7/32']];
        yield 'unaligned' => ['10.0.0.5-10.0.0.20', ['10.0.0.5/32', '10.0.0.6/31', '10.0.0.8/29', '10.0.0.16/30', '10.0.0.20/32']];
        yield 'carries into the first octet' => ['0.255.255.255-1.0.0.0', ['0.255.255.255/32', '1.0.0.0/32']];
        yield 'crosses an octet' => ['10.0.0.254-10.0.1.1', ['10.0.0.254/31', '10.0.1.0/31']];
        yield 'ipv4 whole space' => ['0.0.0.0-255.255.255.255', ['0.0.0.0/0']];
        yield 'top of ipv4' => ['255.255.255.254-255.255.255.255', ['255.255.255.254/31']];
        yield 'ipv6 whole space' => ['::-ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff', ['::/0']];
        yield 'unaligned up to the top of ipv4' => ['255.255.255.253-255.255.255.255', ['255.255.255.253/32', '255.255.255.254/31']];
        yield 'unaligned up to the top of ipv6' => ['ffff:ffff:ffff:ffff:ffff:ffff:ffff:fffd-ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff', ['ffff:ffff:ffff:ffff:ffff:ffff:ffff:fffd/128', 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:fffe/127']];
        yield 'ipv6 unaligned' => ['2001:db8::1-2001:db8::4', ['2001:db8::1/128', '2001:db8::2/127', '2001:db8::4/128']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('toCidrsProvider')]
    public function testToCidrsIsTheMinimalExactCover(string $range, array $expected): void
    {
        $blocks = IpRange::from($range)->toCidrs();

        self::assertSame($expected, \array_map(static fn (Cidr $cidr): string => $cidr->value, $blocks));
    }

    public function testCidrsCoverExactlyTheRange(): void
    {
        $range = IpRange::from('10.1.2.3-10.1.9.200');

        $total = 0;
        foreach ($range->toCidrs() as $block) {
            self::assertTrue(IpRange::fromCidr($block)->isSubrangeOf($range));
            $total += (int) $block->addressCount();
        }

        self::assertSame((int) $range->addressCount(), $total);
    }

    public function testEquality(): void
    {
        self::assertTrue(IpRange::from('2001:db8::1-2001:db8::2')->equals(IpRange::from('2001:DB8::1-2001:DB8::2')));
        self::assertFalse(IpRange::from('10.0.0.1-10.0.0.2')->equals(IpRange::from('10.0.0.1-10.0.0.3')));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function worstCaseProvider(): iterable
    {
        yield 'ipv4' => ['0.0.0.1-255.255.255.254', 62];
        yield 'ipv6' => ['::1-ffff:ffff:ffff:ffff:ffff:ffff:ffff:fffe', 254];
    }

    #[DataProvider('worstCaseProvider')]
    public function testToCidrsWorstCaseIsBounded(string $range, int $expected): void
    {
        self::assertCount($expected, IpRange::from($range)->toCidrs());
    }

    public function testIpv6CidrsAreContiguousAndSumToTheCount(): void
    {
        $range = IpRange::from('2001:db8::3-2001:db8::1:7');
        $blocks = $range->toCidrs();

        self::assertTrue($blocks[0]->firstAddress()->equals($range->firstAddress()));
        self::assertTrue($blocks[\count($blocks) - 1]->lastAddress()->equals($range->lastAddress()));
        for ($i = 1, $n = \count($blocks); $i < $n; $i++) {
            self::assertTrue($blocks[$i - 1]->toRange()->isAdjacentTo($blocks[$i]->toRange()));
        }

        $total = 0;
        foreach ($blocks as $block) {
            $total += (int) $block->addressCount();
        }

        self::assertSame((int) $range->addressCount(), $total);
    }

    public function testIpv4MappedRangeIsIpv6(): void
    {
        $range = IpRange::from('::ffff:10.0.0.0-::ffff:10.0.0.255');

        self::assertSame(6, $range->version());
        self::assertTrue($range->contains(IpAddress::fromString('::ffff:10.0.0.5')));
        self::assertFalse($range->contains(IpAddress::fromString('10.0.0.5')));
        self::assertFalse($range->overlaps(IpRange::from('10.0.0.0-10.0.0.255')));
    }

    public function testContainsARange(): void
    {
        $range = IpRange::from('10.0.0.5-10.0.0.20');

        self::assertTrue($range->contains(IpRange::from('10.0.0.6-10.0.0.20')));
        self::assertFalse($range->contains(IpRange::from('10.0.0.4-10.0.0.20')));
        self::assertFalse($range->contains(IpRange::from('10.0.0.5-10.0.0.21')));
    }

    /**
     * @return iterable<string, array{string, string, string|null}>
     */
    public static function intersectProvider(): iterable
    {
        yield 'partial' => ['10.0.0.1-10.0.0.10', '10.0.0.5-10.0.0.20', '10.0.0.5-10.0.0.10'];
        yield 'contained' => ['10.0.0.1-10.0.0.255', '10.0.0.5-10.0.0.20', '10.0.0.5-10.0.0.20'];
        yield 'one address' => ['10.0.0.1-10.0.0.5', '10.0.0.5-10.0.0.20', '10.0.0.5-10.0.0.5'];
        yield 'disjoint' => ['10.0.0.1-10.0.0.4', '10.0.0.5-10.0.0.20', null];
        // Packed "1e10" and "9000": max()/min() would compare them as numbers.
        yield 'numeric-looking packed starts' => ['49.101.49.48-60.0.0.0', '57.48.48.48-60.0.0.0', '57.48.48.48-60.0.0.0'];
        yield 'numeric-looking packed ends' => ['0.0.0.0-49.101.49.48', '0.0.0.0-57.48.48.48', '0.0.0.0-49.101.49.48'];
        yield 'numeric-looking ipv6' => ['3165:3130:3030:3030:3030:3030:3030:3030-ffff::', '3930:3030:3030:3030:3030:3030:3030:3030-ffff::', '3930:3030:3030:3030:3030:3030:3030:3030-ffff::'];
        yield 'other version' => ['::-::ffff', '0.0.0.0-0.0.255.255', null];
    }

    #[DataProvider('intersectProvider')]
    public function testIntersect(string $a, string $b, ?string $expected): void
    {
        self::assertSame($expected, IpRange::from($a)->intersect(IpRange::from($b))?->value);
        self::assertSame($expected, IpRange::from($b)->intersect(IpRange::from($a))?->value);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function adjacencyProvider(): iterable
    {
        yield 'directly after' => ['10.0.0.1-10.0.0.4', '10.0.0.5-10.0.0.20', true];
        yield 'across an octet' => ['10.0.0.0-10.0.0.255', '10.0.1.0-10.0.1.9', true];
        yield 'gap of one' => ['10.0.0.1-10.0.0.3', '10.0.0.5-10.0.0.20', false];
        yield 'overlapping' => ['10.0.0.1-10.0.0.5', '10.0.0.5-10.0.0.20', false];
        yield 'top of the space' => ['255.255.255.0-255.255.255.255', '0.0.0.0-0.0.0.1', false];
        yield 'other version' => ['::-::ff', '0.0.1.0-0.0.1.1', false];
    }

    #[DataProvider('adjacencyProvider')]
    public function testIsAdjacentTo(string $a, string $b, bool $expected): void
    {
        self::assertSame($expected, IpRange::from($a)->isAdjacentTo(IpRange::from($b)));
        self::assertSame($expected, IpRange::from($b)->isAdjacentTo(IpRange::from($a)));
    }

    public function testCidrToRange(): void
    {
        self::assertSame('2001:db8::-2001:db8::ff', Cidr::from('2001:db8::/120')->toRange()->value);
    }

    public function testTryFromAndIsValidNeverLeakALogicException(): void
    {
        self::assertNull(IpRange::tryFrom('10.0.0.9-10.0.0.1'));
        self::assertNull(IpRange::tryFrom('nope'));
        self::assertFalse(IpRange::isValid('10.0.0.1-::1'));
        self::assertTrue(IpRange::isValid('10.0.0.1-10.0.0.1'));
    }

    public function testJsonSerializesToTheCanonicalString(): void
    {
        self::assertSame('"2001:db8::1-2001:db8::ff"', \json_encode(IpRange::from('2001:DB8::1-2001:DB8::FF'), \JSON_THROW_ON_ERROR));
    }
}
