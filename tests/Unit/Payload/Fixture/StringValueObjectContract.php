<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture;

use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;

/** A contract several concrete string value objects implement: no class the mapper can build. */
interface StringValueObjectContract extends StringConvertible, ValueObject
{
}
