<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Format;

use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidClassStringException;
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
        if (
            !\class_exists($value)
            && !\interface_exists($value)
            && !\trait_exists($value)
            && !\enum_exists($value)
        ) {
            throw new InvalidClassStringException(\sprintf('Unknown class, interface, trait or enum: "%s".', InvalidClassStringException::excerpt($value)));
        }

        $this->value = $value;
    }

    #[Override]
    public function __toString(): string
    {
        return $this->value;
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
}
