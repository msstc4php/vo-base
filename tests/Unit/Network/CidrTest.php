<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidCidrException;
use MSSTC4PHP\ValueObject\Network\Cidr;
use MSSTC4PHP\ValueObject\Network\IpAddress;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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
}
