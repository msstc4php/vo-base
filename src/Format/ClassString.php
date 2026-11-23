<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Format;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidClassStringException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\FreshInstance;
use Override;

/**
 * A fully-qualified name of an existing class, interface, trait or enum.
 *
 * Backed directly (not via the string base) so the stored value can be typed
 * as {@see class-string} rather than a plain string.
 */
final readonly class ClassString implements StringConvertible, ValueObject
{
    /** @var class-string */
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $this->existing($value);
    }

    #[Override]
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @return array{value: class-string}
     */
    public function __serialize(): array
    {
        return ['value' => $this->value];
    }

    /**
     * Runs the constructor's checks again, so a forged or stale payload never yields an instance the constructor
     * would refuse. Other keys are ignored.
     *
     * @param array<array-key, mixed> $data untrusted: whatever the serialized string holds
     *
     * @throws InvalidValueObjectException when a key is missing or of the wrong type, or the values break the type's rules
     * @throws LogicException when called on an object that already holds a value
     */
    public function __unserialize(array $data): void
    {
        FreshInstance::assert($this, 'value');

        $value = $data['value'] ?? null;
        if (!\is_string($value)) {
            throw new InvalidValueObjectException(\sprintf('A serialized %s must hold its value as a string.', self::class));
        }

        $this->value = $this->existing($value);
    }

    #[Override]
    public static function from(string $value): static
    {
        return new self($value);
    }

    public static function tryFrom(string $value): ?self
    {
        try {
            return new self($value);
        } catch (InvalidClassStringException) {
            return null;
        }
    }

    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) instanceof self;
    }

    #[Override]
    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->value === $this->value;
    }

    public function isInstanceOf(object $object): bool
    {
        return $object instanceof $this->value;
    }

    /**
     * @param class-string $parent
     */
    public function isSubclassOf(string $parent): bool
    {
        return \is_subclass_of($this->value, $parent);
    }

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * @return class-string
     *
     * @throws InvalidClassStringException
     */
    private function existing(string $value): string
    {
        if (
            !\class_exists($value)
            && !\interface_exists($value)
            && !\trait_exists($value)
            && !\enum_exists($value)
        ) {
            throw new InvalidClassStringException(\sprintf('Unknown class, interface, trait or enum: "%s".', InvalidClassStringException::excerpt($value)));
        }

        return $value;
    }
}
