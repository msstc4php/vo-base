<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Finance;

use MSSTC4PHP\ValueObject\Exception\InvalidDecimalException;
use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Format\SemVer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Decimal::class)]
final class DecimalTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normalizations(): iterable
    {
        yield 'drops plus and trailing zeros' => ['+1.230', '1.23'];
        yield 'minus zero becomes zero' => ['-0', '0'];
        yield 'strips leading zeros' => ['007', '7'];
        yield 'keeps single leading zero' => ['0.50', '0.5'];
        yield 'negative fraction' => ['-0.50', '-0.5'];
        yield 'trailing dot removed' => ['5.000', '5'];
    }

    #[DataProvider('normalizations')]
    public function testNormalizesValue(string $input, string $expected): void
    {
        self::assertSame($expected, Decimal::from($input)->value);
    }

    public function testComparesAcrossDifferentScales(): void
    {
        self::assertSame(1, Decimal::from('1.5')->compareTo(Decimal::from('1.25')));
        self::assertSame(-1, Decimal::from('1.25')->compareTo(Decimal::from('1.5')));
        self::assertSame(0, Decimal::from('1.50')->compareTo(Decimal::from('1.5')));
    }

    public function testIsZero(): void
    {
        self::assertTrue(Decimal::from('-0')->isZero());
        self::assertTrue(Decimal::from('0.0')->isZero());
        self::assertFalse(Decimal::from('0.1')->isZero());
    }

    public function testCompareToRejectsNonDecimal(): void
    {
        $this->expectException(InvalidDecimalException::class);

        Decimal::from('1')->compareTo(SemVer::from('1.0.0'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDecimals(): iterable
    {
        yield 'letters' => ['1.2a'];
        yield 'trailing dot' => ['1.'];
        yield 'leading dot' => ['.5'];
        yield 'double sign' => ['--1'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidDecimals')]
    public function testRejectsInvalidDecimals(string $value): void
    {
        $this->expectException(InvalidDecimalException::class);

        Decimal::from($value);
    }

    public function testExactArithmetic(): void
    {
        self::assertSame('3.3', Decimal::from('1.1')->add('2.2')->value);
        self::assertSame('-1.1', Decimal::from('1.1')->subtract('2.2')->value);
        self::assertSame('0.02', Decimal::from('0.1')->multiply('0.2')->value);
        self::assertSame('4.2', Decimal::from('-4.20')->abs()->value);
        self::assertSame('-4.2', Decimal::from('4.2')->negated()->value);
    }

    public function testAddAcceptsDecimalIntAndString(): void
    {
        self::assertSame('5', Decimal::from('2')->add(Decimal::from('3'))->value);
        self::assertSame('5', Decimal::from('2')->add(3)->value);
        self::assertSame('5', Decimal::from('2')->add('3')->value);
    }

    public function testDivideWithRounding(): void
    {
        self::assertSame('0.3333', Decimal::from('1')->divide('3', 4)->value);
        self::assertSame('0.25', Decimal::from('1')->divide('4', 2)->value);
    }

    public function testDivideByZeroThrows(): void
    {
        $this->expectException(InvalidDecimalException::class);

        Decimal::from('1')->divide('0', 2);
    }

    public function testRound(): void
    {
        self::assertSame('3', Decimal::from('2.5')->round(0)->value);
        self::assertSame('1.23', Decimal::from('1.2345')->round(2)->value);
    }

    public function testSign(): void
    {
        self::assertSame(-1, Decimal::from('-4.2')->sign());
        self::assertSame(0, Decimal::from('0')->sign());
        self::assertSame(1, Decimal::from('4.2')->sign());
    }

    public function testArithmeticIsImmutable(): void
    {
        $decimal = Decimal::from('1.1');
        $decimal->add('2.2');

        self::assertSame('1.1', $decimal->value);
    }
}
