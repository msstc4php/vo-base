<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture;

use MSSTC4PHP\ValueObject\Contract\ResolvesConcreteClass;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * Carries the marker but inherits the base's from(), which cannot build an abstract class.
 */
abstract class AbstractCodeWithoutFrom extends AbstractStringValueObject implements ResolvesConcreteClass
{
}
