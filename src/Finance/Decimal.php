<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Finance;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use MSSTC4PHP\ValueObject\Contract\Comparable;
use MSSTC4PHP\ValueObject\Exception\InvalidDecimalException;
use MSSTC4PHP\ValueObject\Exception\MissingDependencyException;
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
     * @throws MissingDependencyException when "brick/math" is not installed
     */
    public function add(self|int|string $that): self
    {
        return $this->rebuild(fn (BigDecimal $value): BigDecimal => $value->plus($this->operandOf($that)));
    }

    /**
     * @throws MissingDependencyException when "brick/math" is not installed
     */
    public function subtract(self|int|string $that): self
    {
        return $this->rebuild(fn (BigDecimal $value): BigDecimal => $value->minus($this->operandOf($that)));
    }

    /**
     * @throws MissingDependencyException when "brick/math" is not installed
     */
    public function multiply(self|int|string $that): self
    {
        return $this->rebuild(fn (BigDecimal $value): BigDecimal => $value->multipliedBy($this->operandOf($that)));
    }

    /**
     * @param non-negative-int $scale
     *
     * @throws MissingDependencyException when "brick/math" is not installed
     * @throws InvalidDecimalException when dividing by zero
     */
    public function divide(self|int|string $that, int $scale, ?RoundingMode $mode = null): self
    {
        return $this->rebuild(function (BigDecimal $value) use ($that, $scale, $mode): BigDecimal {
            try {
                return $value->dividedBy($this->operandOf($that), $scale, $mode ?? RoundingMode::HalfUp);
            } catch (MathException $exception) {
                throw new InvalidDecimalException(\sprintf('Cannot divide "%s" by "%s".', $this->value, $this->operandOf($that)), 0, $exception);
            }
        });
    }

    /**
     * @param non-negative-int $scale
     *
     * @throws MissingDependencyException when "brick/math" is not installed
     */
    public function round(int $scale, ?RoundingMode $mode = null): self
    {
        return $this->rebuild(fn (BigDecimal $value): BigDecimal => $value->toScale($scale, $mode ?? RoundingMode::HalfUp));
    }

    /**
     * @throws MissingDependencyException when "brick/math" is not installed
     */
    public function abs(): self
    {
        return $this->rebuild(fn (BigDecimal $value): BigDecimal => $value->abs());
    }

    /**
     * @throws MissingDependencyException when "brick/math" is not installed
     */
    public function negated(): self
    {
        return $this->rebuild(fn (BigDecimal $value): BigDecimal => $value->negated());
    }

    /**
     * The sign: -1 for negative, 0 for zero, 1 for positive.
     *
     * @return -1|0|1
     *
     * @throws MissingDependencyException when "brick/math" is not installed
     */
    public function sign(): int
    {
        $this->requireBrickMath();

        return BigDecimal::of($this->value)->getSign();
    }

    /**
     * @return non-negative-int
     */
    private function fractionLength(): int
    {
        $dot = \strpos($this->value, '.');

        return $dot === false ? 0 : \max(0, \strlen($this->value) - $dot - 1);
    }

    /**
     * Apply a brick/math operation and wrap the result back into a normalized Decimal.
     *
     * @param callable(BigDecimal): BigDecimal $operation
     */
    private function rebuild(callable $operation): self
    {
        $this->requireBrickMath();

        return new self((string) $operation(BigDecimal::of($this->value)));
    }

    private function operandOf(self|int|string $that): string
    {
        return $that instanceof self ? $that->value : (string) $that;
    }

    private function requireBrickMath(): void
    {
        if (!class_exists(BigDecimal::class)) {
            throw new MissingDependencyException('Decimal arithmetic requires the "brick/math" package. Run: composer require brick/math');
        }
    }
}
