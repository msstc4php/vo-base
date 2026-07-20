<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Temporal;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Exception\InvalidDurationException;
use MSSTC4PHP\ValueObject\Temporal\Duration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Duration::class)]
final class DurationTest extends TestCase
{
    public function testAcceptsCompoundDuration(): void
    {
        $duration = Duration::from('P1Y2M10DT2H30M');

        self::assertSame('P1Y2M10DT2H30M', $duration->value);

        $interval = $duration->toDateInterval();

        self::assertSame(1, $interval->y);
        self::assertSame(2, $interval->m);
        self::assertSame(10, $interval->d);
        self::assertSame(2, $interval->h);
        self::assertSame(30, $interval->i);
    }

    public function testAcceptsSecondsOnlyDuration(): void
    {
        $duration = Duration::from('PT30S');

        self::assertSame('PT30S', $duration->value);
        self::assertSame(30, $duration->toDateInterval()->s);
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidDurationException::class);

        Duration::from('');
    }

    public function testRejectsBarePeriodDesignator(): void
    {
        $this->expectException(InvalidDurationException::class);

        Duration::from('P');
    }

    public function testRejectsBareTimeDesignator(): void
    {
        $this->expectException(InvalidDurationException::class);

        Duration::from('PT');
    }

    public function testRejectsMissingPeriodDesignator(): void
    {
        $this->expectException(InvalidDurationException::class);

        Duration::from('1Y');
    }

    public function testRejectsTimeComponentWithoutTimeDesignator(): void
    {
        $this->expectException(InvalidDurationException::class);

        Duration::from('P1H');
    }

    public function testPlusCombinesComponents(): void
    {
        self::assertSame('P1Y2M', Duration::from('P1Y')->plus(Duration::from('P2M'))->value);
        self::assertSame('P1Y', Duration::from('P6M')->plus(Duration::from('P6M'))->value);
        self::assertSame('PT1H10M', Duration::from('PT40M')->plus(Duration::from('PT30M'))->value);
    }

    public function testMinus(): void
    {
        self::assertTrue(Duration::from('P1M')->minus(Duration::from('P1M'))->isZero());
        self::assertSame('PT1H30M', Duration::from('PT2H')->minus(Duration::from('PT30M'))->value);
    }

    public function testMinusNegativeThrows(): void
    {
        $this->expectException(InvalidDurationException::class);

        Duration::from('PT30M')->minus(Duration::from('PT1H'));
    }

    public function testMultipliedBy(): void
    {
        self::assertSame('PT1H30M', Duration::from('PT30M')->multipliedBy(3)->value);
        self::assertSame('P2Y', Duration::from('P1Y')->multipliedBy(2)->value);
        self::assertTrue(Duration::from('P1Y')->multipliedBy(0)->isZero());
    }

    public function testMultipliedByNegativeThrows(): void
    {
        $this->expectException(InvalidDurationException::class);

        Duration::from('PT30M')->multipliedBy(-1);
    }

    public function testToSeconds(): void
    {
        self::assertSame(5400, Duration::from('PT90M')->toSeconds());
        self::assertSame(86400, Duration::from('P1D')->toSeconds());
    }

    public function testToSecondsRequiresAnchorForCalendarComponents(): void
    {
        $this->expectException(InvalidDurationException::class);

        Duration::from('P1M')->toSeconds();
    }

    public function testToSecondsWithAnchor(): void
    {
        $anchor = new DateTimeImmutable('2026-01-01T00:00:00Z');

        self::assertSame(2678400, Duration::from('P1M')->toSeconds($anchor));
    }

    public function testFromSeconds(): void
    {
        self::assertSame('PT1H', Duration::fromSeconds(3600)->value);
        self::assertSame('PT0S', Duration::fromSeconds(0)->value);
    }

    public function testFromSecondsNegativeThrows(): void
    {
        $this->expectException(InvalidDurationException::class);

        Duration::fromSeconds(-1);
    }

    public function testIsImmutable(): void
    {
        $duration = Duration::from('PT30M');
        $duration->plus(Duration::from('PT30M'));

        self::assertSame('PT30M', $duration->value);
    }
}
