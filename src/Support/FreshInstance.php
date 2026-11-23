<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

use LogicException;
use ReflectionProperty;

/**
 * PHP calls __unserialize() on a new, uninitialized object; an initialized property means it was called on a live
 * value object, which must stay immutable.
 *
 * @internal
 */
final class FreshInstance
{
    /**
     * @throws LogicException when $property of $object is already initialized
     */
    public static function assert(object $object, string $property): void
    {
        if (new ReflectionProperty($object, $property)->isInitialized($object)) {
            throw new LogicException('A value object is immutable; __unserialize() runs only on a new object.');
        }
    }
}
