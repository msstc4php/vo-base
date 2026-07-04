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
}
