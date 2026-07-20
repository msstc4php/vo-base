<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Format\SemVer;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Support\ComparableHelpers;
use MSSTC4PHP\ValueObject\Temporal\TimeOfDay;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversTrait(ComparableHelpers::class)]
final class ComparableHelpersTest extends TestCase
{
    public function testPredicatesAgreeWithCompareToSign(): void
    {
        $low = Port::from(80);
        $high = Port::from(443);

        self::assertTrue($low->isLessThan($high));
        self::assertTrue($low->isLessThanOrEqual($high));
        self::assertFalse($low->isGreaterThan($high));
        self::assertFalse($low->isGreaterThanOrEqual($high));

        self::assertTrue($high->isGreaterThan($low));
        self::assertTrue($high->isGreaterThanOrEqual($low));
    }

    public function testEqualOperandsBoundaries(): void
    {
        $a = Port::from(8080);
        $b = Port::from(8080);

        self::assertFalse($a->isLessThan($b));
        self::assertFalse($a->isGreaterThan($b));
        self::assertTrue($a->isLessThanOrEqual($b));
        self::assertTrue($a->isGreaterThanOrEqual($b));
    }

    public function testMinAndMaxSelectTheCorrectInstance(): void
    {
        $low = Port::from(80);
        $high = Port::from(443);

        self::assertSame($low, $low->min($high));
        self::assertSame($low, $high->min($low));
        self::assertSame($high, $low->max($high));
        self::assertSame($high, $high->max($low));
    }

    public function testMinOfEqualsReturnsAValueEqualInstance(): void
    {
        $a = Port::from(22);
        $b = Port::from(22);

        self::assertTrue($a->min($b)->equals($b));
        self::assertTrue($a->max($b)->equals($b));
    }

    public function testHelpersWorkAcrossValueObjectFamilies(): void
    {
        self::assertTrue(Decimal::from('1.5')->isLessThan(Decimal::from('1.50001')));
        self::assertTrue(TimeOfDay::from('08:30')->isLessThan(TimeOfDay::from('08:30:01')));
    }

    /**
     * Invariant: min returns the lower operand and max the higher, for every
     * Comparable family. min/max are trait methods (not on the Comparable
     * interface), so each family is exercised through its concrete type.
     */
    public function testMinMaxAgreeWithCompareToAcrossFamilies(): void
    {
        $lowPort = Port::from(80);
        $highPort = Port::from(443);
        self::assertSame($lowPort, $lowPort->min($highPort));
        self::assertSame($lowPort, $highPort->min($lowPort));
        self::assertSame($highPort, $lowPort->max($highPort));

        $lowDecimal = Decimal::from('2.5');
        $highDecimal = Decimal::from('2.6');
        self::assertSame($lowDecimal, $lowDecimal->min($highDecimal));
        self::assertSame($highDecimal, $lowDecimal->max($highDecimal));

        $lowVer = SemVer::from('1.2.3');
        $highVer = SemVer::from('1.10.0');
        self::assertSame($lowVer, $lowVer->min($highVer));
        self::assertSame($highVer, $lowVer->max($highVer));

        $earlier = TimeOfDay::from('06:30');
        $later = TimeOfDay::from('23:00');
        self::assertSame($earlier, $later->min($earlier));
        self::assertSame($later, $earlier->max($later));
    }
}
