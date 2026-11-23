<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Primitive;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidBoundedStringException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\FreshInstance;
use Override;
use Stringable;

/**
 * A string whose length (in Unicode characters) is constrained to per-instance
 * bounds. Because the bounds are part of the value, this cannot use the shared
 * string base and carries its own construction and equality logic.
 */
final readonly class BoundedString implements ValueObject, Stringable
{
    public function __construct(
        public string $value,
        public int $minLength = 0,
        public ?int $maxLength = null,
    ) {
        $this->check($value, $minLength, $maxLength);
    }

    #[Override]
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @return array{value: string, minLength: int, maxLength: int|null}
     */
    public function __serialize(): array
    {
        return ['value' => $this->value, 'minLength' => $this->minLength, 'maxLength' => $this->maxLength];
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
        $minLength = $data['minLength'] ?? null;
        $maxLength = $data['maxLength'] ?? null;
        if (!\is_string($value) || !\is_int($minLength) || !\array_key_exists('maxLength', $data) || ($maxLength !== null && !\is_int($maxLength))) {
            throw new InvalidValueObjectException(\sprintf('A serialized %s must hold a string value, an int minLength and an int or null maxLength.', self::class));
        }

        $this->check($value, $minLength, $maxLength);
        $this->value = $value;
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
    }

    #[Override]
    public function equals(ValueObject $other): bool
    {
        return $other instanceof self
            && $other->value === $this->value
            && $other->minLength === $this->minLength
            && $other->maxLength === $this->maxLength;
    }

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * @throws InvalidBoundedStringException
     */
    private function check(string $value, int $minLength, ?int $maxLength): void
    {
        if ($minLength < 0) {
            throw new InvalidBoundedStringException(\sprintf('Minimum length must not be negative, got %d.', $minLength));
        }

        if ($maxLength !== null && $maxLength < $minLength) {
            throw new InvalidBoundedStringException(\sprintf('Maximum length %d must not be less than minimum length %d.', $maxLength, $minLength));
        }

        $length = \mb_strlen($value);
        $upperBound = $maxLength ?? \PHP_INT_MAX;

        if ($length < $minLength || $length > $upperBound) {
            throw new InvalidBoundedStringException(\sprintf('Length %d is outside the allowed range %d..%s.', $length, $minLength, $maxLength ?? 'unbounded'));
        }
    }
}
