<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Temporal;

use MSSTC4PHP\ValueObject\Exception\InvalidTimeOfDayException;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Temporal\Duration;
use MSSTC4PHP\ValueObject\Temporal\TimeOfDay;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testPlusWrapsAroundMidnight(): void
    {
        self::assertSame('00:30:00', TimeOfDay::from('23:30')->plus(Duration::from('PT1H'))->value);
        self::assertSame('10:15:30', TimeOfDay::from('08:00')->plus(Duration::from('PT2H15M30S'))->value);
    }

    public function testMinus(): void
    {
        self::assertSame('07:30:00', TimeOfDay::from('08:00')->minus(Duration::from('PT30M'))->value);
        self::assertSame('23:30:00', TimeOfDay::from('00:00')->minus(Duration::from('PT30M'))->value);
    }

    public function testShiftCountsStartingSeconds(): void
    {
        self::assertSame('10:00:45', TimeOfDay::from('10:00:15')->plus(Duration::from('PT30S'))->value);
        self::assertSame('09:59:45', TimeOfDay::from('10:00:15')->minus(Duration::from('PT30S'))->value);
    }

    /**
     * @param non-empty-string $duration
     */
    #[DataProvider('dateComponentProvider')]
    public function testRejectsDurationWithDateComponents(string $duration): void
    {
        $this->expectException(InvalidTimeOfDayException::class);

        TimeOfDay::from('12:00')->plus(Duration::from($duration));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function dateComponentProvider(): iterable
    {
        yield 'days' => ['P1D'];
        yield 'months' => ['P1M'];
        yield 'years' => ['P1Y'];
    }
}
