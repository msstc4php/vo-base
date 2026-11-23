<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ResolvesConcreteClass;
use Override;

/**
 * Carries the marker and its own from() but is no value object.
 */
abstract class ResolvingNonValueObject implements ResolvesConcreteClass
{
    #[Override]
    public static function from(string $value): static
    {
        throw new LogicException('Never built.');
    }
}
