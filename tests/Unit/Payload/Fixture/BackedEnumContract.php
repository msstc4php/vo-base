<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture;

use BackedEnum;

/** A contract several backed enums implement: no enum the mapper can read. */
interface BackedEnumContract extends BackedEnum
{
}
