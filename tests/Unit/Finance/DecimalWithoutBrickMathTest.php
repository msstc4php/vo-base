<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Finance;

use Brick\Math\BigDecimal;
use MSSTC4PHP\ValueObject\Exception\MissingDependencyException;
use MSSTC4PHP\ValueObject\Finance\Decimal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Proves the arithmetic guard: without "brick/math" installed, construction and
 * validation still work, but arithmetic throws {@see MissingDependencyException}.
 * The full suite ships brick/math, so this skips there; it runs for real in the
 * "core-only" CI leg (composer install --no-dev), which excludes brick/math.
 */
#[CoversClass(Decimal::class)]
final class DecimalWithoutBrickMathTest extends TestCase
{
    protected function setUp(): void
    {
        if (class_exists(BigDecimal::class)) {
            self::markTestSkipped('brick/math is installed; the guard path is exercised in the core-only CI leg.');
        }
    }

    public function testConstructionAndComparisonWorkWithoutBrickMath(): void
    {
        self::assertSame('1.5', Decimal::from('1.50')->value);
        self::assertTrue(Decimal::from('0')->isZero());
        self::assertSame(-1, Decimal::from('1')->compareTo(Decimal::from('2')));
    }

    public function testArithmeticThrowsWithoutBrickMath(): void
    {
        $this->expectException(MissingDependencyException::class);

        Decimal::from('1')->add('2');
    }
}
