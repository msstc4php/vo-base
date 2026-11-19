<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidOuiException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Network\Eui64;
use MSSTC4PHP\ValueObject\Network\MacAddress;
use MSSTC4PHP\ValueObject\Network\Oui;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Oui::class)]
#[UsesClass(InvalidValueObjectException::class)]
#[UsesClass(MacAddress::class)]
#[UsesClass(Eui64::class)]
final class OuiTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function notationProvider(): iterable
    {
        yield 'colon' => ['AA:BB:CC'];
        yield 'hyphen' => ['aa-bb-cc'];
        yield 'bare' => ['AaBbCc'];
    }

    #[DataProvider('notationProvider')]
    public function testAllNotationsGiveTheCanonicalForm(string $input): void
    {
        self::assertSame('aa:bb:cc', Oui::from($input)->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'two octets' => ['aa:bb'];
        yield 'four octets' => ['aa:bb:cc:dd'];
        yield 'mixed separators' => ['aa:bb-cc'];
        yield 'dotted' => ['aabb.cc'];
        yield 'not hex' => ['gg:bb:cc'];
        yield 'trailing newline' => ["aa:bb:cc\n"];
        yield 'five digits' => ['aabbc'];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidThrows(string $input): void
    {
        $this->expectException(InvalidOuiException::class);

        Oui::from($input);
    }

    public function testOfAMacAddress(): void
    {
        self::assertSame('00:1a:2b', MacAddress::from('00-1A-2B-3C-4D-5E')->oui()->value);
    }

    public function testOfAnEui64(): void
    {
        self::assertSame('00:1a:2b', Eui64::from('00-1A-2B-FF-FE-3C-4D-5E')->oui()->value);
        self::assertTrue(Eui64::from('00:1a:2b:ff:fe:3c:4d:5e')->oui()->equals(MacAddress::from('001a.2b3c.4d5e')->oui()));
    }
}
