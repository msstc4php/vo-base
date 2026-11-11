<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Payload;

use DateTimeImmutable;
use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidPayloadException;
use MSSTC4PHP\ValueObject\Temporal\DateRange;
use Override;

/**
 * {start: string, end: string} of ISO 8601 instants.
 *
 * @implements PayloadCodec<DateRange>
 */
final readonly class DateRangeCodec implements PayloadCodec
{
    /**
     * Microseconds plus a numeric offset. The core's jsonSerialize() uses ATOM,
     * which carries neither -- a DateRange with microseconds or a named zone was
     * written happily and came back different, equals() returning false.
     *
     * The offset is deliberate rather than the zone name: local time plus a zone
     * name is ambiguous in the repeated hour of a DST fall-back, and restoring
     * from it picks the first of the two instants. Measured on the second
     * 01:30:00.123456 in America/New_York, that shifts the value back one hour;
     * the offset form round-trips both instants exactly. DateRange::equals()
     * compares instants, so the offset is sufficient.
     */
    private const string BOUNDARY_FORMAT = 'Y-m-d\TH:i:s.uP';

    /**
     * Read strictly: the payload may come from a request now, and the permissive
     * DateTimeImmutable constructor would accept "now" or a wall time without an
     * offset, silently taken in the server's zone. Seconds-only is the form rows
     * had before microseconds were kept, and what API clients usually send. X
     * instead of Y reads back what Y writes for years before 1 or after 9999.
     */
    private const array READ_FORMATS = ['!X-m-d\TH:i:s.uP', '!X-m-d\TH:i:sP'];

    /**
     * The year exactly as Y writes it: four digits, more only without a leading
     * zero, a minus for BCE. X alone also takes "26" (year 26) or "+2026".
     */
    private const string YEAR = '/^-?(?:\d{4}|[1-9]\d{4,})-/D';

    #[Override]
    public function valueObjectClass(): string
    {
        return DateRange::class;
    }

    /**
     * @return array{start: string, end: string}
     */
    #[Override]
    public function encode(ValueObject $value): array
    {
        if (!$value instanceof DateRange) {
            throw new LogicException(\sprintf('%s encodes %s, %s given.', self::class, DateRange::class, $value::class));
        }

        return [
            'start' => $value->start->format(self::BOUNDARY_FORMAT),
            'end' => $value->end->format(self::BOUNDARY_FORMAT),
        ];
    }

    #[Override]
    public function decode(array $payload): DateRange
    {
        $start = $payload['start'] ?? null;
        $end = $payload['end'] ?? null;
        if (!\is_string($start) || !\is_string($end)) {
            throw new InvalidPayloadException('Expected a date-range payload with string "start" and "end".');
        }

        return new DateRange($this->instant($start), $this->instant($end));
    }

    private function instant(string $text): DateTimeImmutable
    {
        foreach (preg_match(self::YEAR, $text) === 1 ? self::READ_FORMATS : [] as $format) {
            $instant = DateTimeImmutable::createFromFormat($format, $text);
            if ($instant !== false && DateTimeImmutable::getLastErrors() === false) {
                return $instant;
            }
        }

        throw new InvalidPayloadException(\sprintf('Expected an ISO 8601 instant with an offset in "start" and "end", got "%s".', InvalidPayloadException::excerpt($text)));
    }
}
