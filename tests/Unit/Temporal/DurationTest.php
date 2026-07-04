<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Temporal;

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
}
