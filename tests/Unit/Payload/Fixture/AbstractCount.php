<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture;

use MSSTC4PHP\ValueObject\Support\AbstractIntValueObject;

/** An int-backed base class left abstract: no class the mapper can build. */
abstract class AbstractCount extends AbstractIntValueObject
{
}
