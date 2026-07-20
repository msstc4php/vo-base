<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Temporal;

use MSSTC4PHP\ValueObject\Exception\InvalidTimeOfDayException;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Temporal\TimeOfDay;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimeOfDay::class)]
final class TimeOfDayTest extends TestCase
{
    public function testNormalizesMissingSeconds(): void
    {
        $time = TimeOfDay::from('09:30');

        self::assertSame('09:30:00', $time->value);
        self::assertSame(9, $time->hour());
        self::assertSame(30, $time->minute());
        self::assertSame(0, $time->second());
    }

    public function testAcceptsFullTimeWithSeconds(): void
    {
        $time = TimeOfDay::from('23:59:59');

        self::assertSame('23:59:59', $time->value);
        self::assertSame(23, $time->hour());
        self::assertSame(59, $time->minute());
        self::assertSame(59, $time->second());
    }

    public function testRejectsHourOutOfRange(): void
    {
        $this->expectException(InvalidTimeOfDayException::class);

        TimeOfDay::from('24:00');
    }

    public function testRejectsMinuteOutOfRange(): void
    {
        $this->expectException(InvalidTimeOfDayException::class);

        TimeOfDay::from('12:60');
    }

    public function testRejectsMissingLeadingZero(): void
    {
        $this->expectException(InvalidTimeOfDayException::class);

        TimeOfDay::from('9:30');
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidTimeOfDayException::class);

        TimeOfDay::from('');
    }

    public function testComparesChronologically(): void
    {
        self::assertSame(-1, TimeOfDay::from('00:00:00')->compareTo(TimeOfDay::from('23:59:59')));
        self::assertSame(1, TimeOfDay::from('23:59:59')->compareTo(TimeOfDay::from('00:00:00')));
        self::assertSame(0, TimeOfDay::from('12:00')->compareTo(TimeOfDay::from('12:00:00')));
        self::assertSame(-1, TimeOfDay::from('08:30')->compareTo(TimeOfDay::from('08:30:01')));
    }

    public function testCompareToRejectsForeignType(): void
    {
        $this->expectException(InvalidTimeOfDayException::class);

        TimeOfDay::from('12:00')->compareTo(Port::from(80));
    }
}
