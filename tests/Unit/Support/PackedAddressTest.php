<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use LogicException;
use MSSTC4PHP\ValueObject\Support\PackedAddress;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

#[CoversClass(PackedAddress::class)]
final class PackedAddressTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rfc5952Provider(): iterable
    {
        yield 'unspecified' => ['::', '::'];
        yield 'loopback' => ['0:0:0:0:0:0:0:1', '::1'];
        yield 'trailing run' => ['1:0:0:0:0:0:0:0', '1::'];
        yield 'lowercase, no leading zeros' => ['2001:0DB8:00AB:0000:0000:0000:0000:0001', '2001:db8:ab::1'];
        yield 'single zero group is not compressed' => ['2001:db8:0:1:1:1:1:1', '2001:db8:0:1:1:1:1:1'];
        yield 'leftmost of equal runs' => ['2001:db8:0:0:1:0:0:1', '2001:db8::1:0:0:1'];
        yield 'longest run wins' => ['2001:0:0:1:0:0:0:1', '2001:0:0:1::1'];
        yield 'ipv4-mapped keeps the dotted tail' => ['::ffff:1.2.3.4', '::ffff:1.2.3.4'];
        yield 'ipv4-compatible is plain hex on every platform' => ['::1.2.3.4', '::102:304'];
        yield 'ipv4' => ['192.0.2.1', '192.0.2.1'];
    }

    #[DataProvider('rfc5952Provider')]
    public function testUnpackRendersRfc5952(string $input, string $expected): void
    {
        self::assertSame($expected, PackedAddress::unpack(PackedAddress::pack($input)));
    }

    public function testUnpackAgreesWithInetNtopOutsideTheIpv4CompatibleBlock(): void
    {
        $random = new Randomizer(new Xoshiro256StarStar(5952));
        for ($i = 0; $i < 2000; $i++) {
            $packed = $random->getBytes(16);
            // Sprinkle zero groups so runs of every length occur.
            for ($group = 0; $group < 8; $group++) {
                if ($random->getInt(0, 2) === 0) {
                    $packed = \substr_replace($packed, "\x00\x00", $group * 2, 2);
                }
            }

            if (\str_starts_with($packed, \str_repeat("\x00", 12))) {
                continue;
            }

            self::assertSame(\inet_ntop($packed), PackedAddress::unpack($packed), \bin2hex($packed));
        }
    }

    public function testRejectsAnythingButFourOrSixteenBytes(): void
    {
        $this->expectException(LogicException::class);

        PackedAddress::byteLength('abc');
    }

    /**
     * @return iterable<string, array{4|16, int<0, 128>, string}>
     */
    public static function lowBitsMaskProvider(): iterable
    {
        yield 'v6 none' => [16, 0, \str_repeat("\x00", 16)];
        yield 'v6 one' => [16, 1, \str_repeat("\x00", 15) . "\x01"];
        yield 'v6 nine' => [16, 9, \str_repeat("\x00", 14) . "\x01\xFF"];
        yield 'v6 all' => [16, 128, \str_repeat("\xFF", 16)];
        yield 'v4 all' => [4, 32, "\xFF\xFF\xFF\xFF"];
    }

    /**
     * @param 4|16 $bytes
     * @param int<0, 128> $bits
     */
    #[DataProvider('lowBitsMaskProvider')]
    public function testLowBitsMask(int $bytes, int $bits, string $expected): void
    {
        self::assertSame(\bin2hex($expected), \bin2hex(PackedAddress::lowBitsMask($bytes, $bits)));
    }

    public function testTrailingZeroBits(): void
    {
        self::assertSame(128, PackedAddress::trailingZeroBits(\str_repeat("\x00", 16)));
        self::assertSame(32, PackedAddress::trailingZeroBits("\x00\x00\x00\x00"));
        self::assertSame(0, PackedAddress::trailingZeroBits("\x00\x00\x00\x01"));
        self::assertSame(11, PackedAddress::trailingZeroBits("\x00\x00\x08\x00"));
    }

    public function testIncrementCarriesAndStopsAtTheTop(): void
    {
        self::assertSame("\x00\x01\x00\x00", PackedAddress::increment("\x00\x00\xFF\xFF"));
        self::assertSame("\x01\x00\x00\x00", PackedAddress::increment("\x00\xFF\xFF\xFF"));
        self::assertNull(PackedAddress::increment("\xFF\xFF\xFF\xFF"));
        self::assertNull(PackedAddress::increment(\str_repeat("\xFF", 16)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidCountProvider(): iterable
    {
        yield 'reversed' => ["\x00\x00\x00\x02", "\x00\x00\x00\x01"];
        yield 'mixed lengths' => ["\x00\x00\x00\x01", \str_repeat("\x00", 16)];
    }

    #[DataProvider('invalidCountProvider')]
    public function testInclusiveCountRejectsBrokenPreconditions(string $first, string $last): void
    {
        $this->expectException(LogicException::class);

        PackedAddress::inclusiveCount($first, $last);
    }
}
