<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Contract;

use JsonSerializable;

/**
 * Marker contract for every value object: immutable, value-equal, JSON-serializable.
 */
interface ValueObject extends JsonSerializable
{
    /**
     * Value equality: same concrete type and same underlying value.
     */
    public function equals(self $other): bool;
}
