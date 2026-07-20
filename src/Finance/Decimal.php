<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Finance;

use MSSTC4PHP\ValueObject\Contract\Comparable;
use MSSTC4PHP\ValueObject\Exception\InvalidDecimalException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Support\ComparableHelpers;

/**
 * Arbitrary-precision decimal number stored as a normalized numeric string.
 * Comparison uses bcmath at the wider of the two operands' scales.
 */
final class Decimal extends AbstractStringValueObject implements Comparable
{
    use ComparableHelpers;

    private const string PATTERN = '/^[+-]?\d+(\.\d+)?$/';

    protected function parse(string $value): string
    {
        if (\preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidDecimalException(\sprintf('Invalid decimal: "%s".', $value));
        }

        $negative = $value[0] === '-';
        $unsigned = \ltrim($value, '+-');

        [$integer, $fraction] = \str_contains($unsigned, '.')
            ? \explode('.', $unsigned, 2)
            : [$unsigned, ''];

        $integer = \ltrim($integer, '0');
        if ($integer === '') {
            $integer = '0';
        }

        $fraction = \rtrim($fraction, '0');
        $normalized = $fraction === '' ? $integer : $integer . '.' . $fraction;

        return $negative && $normalized !== '0' ? '-' . $normalized : $normalized;
    }

    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new InvalidDecimalException('Decimal can only be compared with another Decimal.');
        }

        // Both values are normalized numeric strings produced by parse().
        \assert(\is_numeric($this->value) && \is_numeric($other->value));

        $scale = \max($this->fractionLength(), $other->fractionLength());

        return \bccomp($this->value, $other->value, $scale);
    }

    public function isZero(): bool
    {
        return $this->value === '0';
    }

    /**
     * @return non-negative-int
     */
    private function fractionLength(): int
    {
        $dot = \strpos($this->value, '.');

        return $dot === false ? 0 : \max(0, \strlen($this->value) - $dot - 1);
    }
}
