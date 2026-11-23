<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture;

use Override;

final class LongCode extends AbstractCode
{
    #[Override]
    protected function pattern(): string
    {
        return '/^[a-z]{4,6}$/D';
    }
}
