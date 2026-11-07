<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
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
