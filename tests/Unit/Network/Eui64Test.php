<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidEui64Exception;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Network\Eui64;
use MSSTC4PHP\ValueObject\Network\MacAddress;
use MSSTC4PHP\ValueObject\Network\Oui;
use MSSTC4PHP\ValueObject\Support\HexOctets;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(HexOctets::class)]
#[CoversClass(Eui64::class)]
#[UsesClass(InvalidValueObjectException::class)]
#[UsesClass(Oui::class)]
#[UsesClass(MacAddress::class)]
final class Eui64Test extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function notationProvider(): iterable
    {
        yield 'colon' => ['00:1A:2B:FF:FE:3C:4D:5E'];
        yield 'hyphen' => ['00-1a-2b-ff-fe-3c-4d-5e'];
        yield 'dotted' => ['001A.2BFF.FE3C.4D5E'];
    }

    #[DataProvider('notationProvider')]
    public function testAllNotationsGiveTheCanonicalForm(string $input): void
    {
        self::assertSame('00:1a:2b:ff:fe:3c:4d:5e', Eui64::from($input)->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'a MAC address' => ['00:1a:2b:3c:4d:5e'];
        yield 'nine octets' => ['00:1a:2b:ff:fe:3c:4d:5e:6f'];
        yield 'mixed separators' => ['00:1a:2b:ff-fe:3c:4d:5e'];
        yield 'bare' => ['001a2bfffe3c4d5e'];
        yield 'dotted with three groups' => ['001a.2bff.fe3c'];
        yield 'not hex' => ['0g:1a:2b:ff:fe:3c:4d:5e'];
        yield 'trailing newline' => ["00:1a:2b:ff:fe:3c:4d:5e\n"];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidThrows(string $input): void
    {
        $this->expectException(InvalidEui64Exception::class);

        Eui64::from($input);
    }

    public function testDoesNotAcceptAMacAddressAndViceVersa(): void
    {
        self::assertFalse(Eui64::isValid('00:1a:2b:3c:4d:5e'));
        self::assertFalse(MacAddress::isValid('00:1a:2b:ff:fe:3c:4d:5e'));
    }
}
