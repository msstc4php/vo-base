<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Format;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidClassStringException;
use Stringable;

/**
 * A fully-qualified name of an existing class, interface, trait or enum.
 *
 * Backed directly (not via the string base) so the stored value can be typed
 * as {@see class-string} rather than a plain string.
 */
final readonly class ClassString implements ValueObject, Stringable
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
            throw new InvalidClassStringException(\sprintf('Unknown class, interface, trait or enum: "%s".', $value));
        }

        $this->value = $value;
    }

    public static function from(string $value): self
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

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
