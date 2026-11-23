<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Contract;

/**
 * An abstract string value object whose static {@see from()} builds the one concrete subclass the value fits, by a
 * rule the value itself settles (its length, its line count), and throws when none fits. It never chooses between
 * several subclasses that all accept the value.
 *
 * The mapper, and every bridge through it, accepts such a class as a column or parameter type although it is abstract:
 * `from()` is the only entry it calls, and the stored form is the value itself. The class must declare that `from()`
 * itself (one inherited from the string base cannot build an abstract class); the mapper refuses it otherwise.
 *
 * The concrete subclasses inherit the marker: their `from()` throws on a value that fits another subclass. An interface
 * extending this one is not mapped (there is nothing to call `from()` on), and a codec registered for the abstract
 * class is not found when an instance is stored, since the mapper looks codecs up by the instance's own class.
 */
interface ResolvesConcreteClass extends StringConvertible
{
}
