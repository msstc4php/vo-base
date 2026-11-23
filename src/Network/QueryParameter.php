<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Support\FreshInstance;
use Override;

/**
 * One decoded name/value pair of a {@see QueryString}. A bare "flag" without
 * "=" decodes to an empty value, and "=x" to an empty name; neither is rejected.
 */
final readonly class QueryParameter implements ValueObject
{
    public function __construct(
        public string $name,
        public string $value,
    ) {
    }

    /**
     * @return array{name: string, value: string}
     */
    public function __serialize(): array
    {
        return ['name' => $this->name, 'value' => $this->value];
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
        FreshInstance::assert($this, 'name');

        $name = $data['name'] ?? null;
        $value = $data['value'] ?? null;
        if (!\is_string($name) || !\is_string($value)) {
            throw new InvalidValueObjectException(\sprintf('A serialized %s must hold name and value as strings.', self::class));
        }

        $this->name = $name;
        $this->value = $value;
    }

    #[Override]
    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->name === $this->name && $other->value === $this->value;
    }

    /**
     * @return array{name: string, value: string}
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return ['name' => $this->name, 'value' => $this->value];
    }
}
