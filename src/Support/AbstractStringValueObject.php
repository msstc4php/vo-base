<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use Override;

/**
 * Base for string-backed value objects. Subclasses implement {@see parse()} to
 * validate the raw input and return the canonical stored form; every other
 * concern (construction convention, equality, serialization) is provided here.
 */
abstract class AbstractStringValueObject implements StringConvertible, ValueObject
{
    public readonly string $value;

    final public function __construct(string $value)
    {
        $this->value = $this->parse($value);
    }

    #[Override]
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @return array{value: string}
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
     * @throws InvalidValueObjectException when the data holds no string value, or the value is not valid
     * @throws LogicException when called on an object that already holds a value
     */
    public function __unserialize(array $data): void
    {
        FreshInstance::assert($this, 'value');

        $value = $data['value'] ?? null;
        if (!\is_string($value)) {
            throw new InvalidValueObjectException(\sprintf('A serialized %s must hold its value as a string.', static::class));
        }

        $this->value = $this->parse($value);
    }

    #[Override]
    public static function from(string $value): static
    {
        return new static($value);
    }

    public static function tryFrom(string $value): ?static
    {
        try {
            return new static($value);
        } catch (InvalidValueObjectException) {
            return null;
        }
    }

    public static function isValid(string $value): bool
    {
        return static::tryFrom($value) instanceof static;
    }

    #[Override]
    public function equals(ValueObject $other): bool
    {
        return $other instanceof static && $other->value === $this->value;
    }

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * Validate the raw input and return the canonical form to store.
     *
     * @throws InvalidValueObjectException when the input is not a valid value
     */
    abstract protected function parse(string $value): string;
}
