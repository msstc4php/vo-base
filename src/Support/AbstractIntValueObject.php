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
 * Base for integer-backed value objects with a natural ordering.
 */
abstract class AbstractIntValueObject implements Comparable, ValueObject, Stringable
{
    use ComparableHelpers;

    public readonly int $value;

    final public function __construct(int $value)
    {
        $this->value = $this->parse($value);
    }

    #[Override]
    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * @return array{value: int}
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
     * @throws InvalidValueObjectException when the data holds no int value, or the value is not valid
     * @throws LogicException when called on an object that already holds a value
     */
    public function __unserialize(array $data): void
    {
        if (isset($this->value)) {
            throw new LogicException('A value object is immutable; __unserialize() runs only on a new object.');
        }

        $value = $data['value'] ?? null;
        if (!\is_int($value)) {
            throw new InvalidValueObjectException(\sprintf('A serialized %s must hold its value as an int.', static::class));
        }

        $this->value = $this->parse($value);
    }

    public static function from(int $value): static
    {
        return new static($value);
    }

    public static function tryFrom(int $value): ?static
    {
        try {
            return new static($value);
        } catch (InvalidValueObjectException) {
            return null;
        }
    }

    public static function isValid(int $value): bool
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
    public function jsonSerialize(): int
    {
        return $this->value;
    }

    /**
     * @throws InvalidValueObjectException when the input is out of range
     */
    abstract protected function parse(int $value): int;
}
