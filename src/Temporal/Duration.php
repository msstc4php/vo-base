<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Temporal;

use DateInterval;
use Exception;
use MSSTC4PHP\ValueObject\Exception\InvalidDurationException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * An ISO 8601 duration (e.g. "P1Y2M10DT2H30M"), stored uppercased.
 */
final class Duration extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        $normalized = \strtoupper($value);

        if (\preg_match('/^P(?!$)(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(?!$)(\d+H)?(\d+M)?(\d+S)?)?$/', $normalized) !== 1) {
            throw new InvalidDurationException(\sprintf('Invalid ISO 8601 duration: "%s".', $value));
        }

        try {
            new DateInterval($normalized);
        } catch (Exception $exception) {
            throw new InvalidDurationException(\sprintf('Invalid ISO 8601 duration: "%s".', $value), 0, $exception);
        }

        return $normalized;
    }

    public function toDateInterval(): DateInterval
    {
        return new DateInterval($this->value);
    }
}
