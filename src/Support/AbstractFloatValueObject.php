<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\Comparable;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use Override;
use Stringable;

/**
 * Base for float-backed value objects with a natural ordering.
 */
abstract class AbstractFloatValueObject implements Comparable, ValueObject, Stringable
{
    use ComparableHelpers;

    public readonly float $value;

    final public function __construct(float $value)
    {
        $this->value = $this->parse($value);
    }

    #[Override]
    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * @return array{value: float}
     */
    public function __serialize(): array
    {
        return ['value' => $this->value];
    }

    /**
     * Runs {@see parse()} again, so a forged or stale payload never yields an
     * instance that {@see from()} would refuse. Other keys are ignored.
     *
     * @param array<array-key, mixed> $data untrusted: whatever the serialized string holds
     *
     * @throws InvalidValueObjectException when the data holds no float value (an int is taken as a float), or the value is not valid
     * @throws LogicException when called on an object that already holds a value
     */
    public function __unserialize(array $data): void
    {
        FreshInstance::assert($this, 'value');

        $value = $data['value'] ?? null;
        if (\is_int($value)) {
            $value = (float) $value;
        }

        if (!\is_float($value)) {
            throw new InvalidValueObjectException(\sprintf('A serialized %s must hold its value as a float.', static::class));
        }

        $this->value = $this->parse($value);
    }

    public static function from(float $value): static
    {
        return new static($value);
    }

    public static function tryFrom(float $value): ?static
    {
        try {
            return new static($value);
        } catch (InvalidValueObjectException) {
            return null;
        }
    }

    public static function isValid(float $value): bool
    {
        return static::tryFrom($value) instanceof static;
    }

    #[Override]
    public function equals(ValueObject $other): bool
    {
        return $other instanceof static && $other->value === $this->value;
    }

    #[Override]
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof static) {
            throw new InvalidValueObjectException(\sprintf('Cannot compare %s with %s.', static::class, $other::class));
        }

        return $this->value <=> $other->value;
    }

    #[Override]
    public function jsonSerialize(): float
    {
        return $this->value;
    }

    /**
     * @throws InvalidValueObjectException when the input is out of range
     */
    abstract protected function parse(float $value): float;
}
