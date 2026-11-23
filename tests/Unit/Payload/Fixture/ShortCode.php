<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture;

use Override;

final class ShortCode extends AbstractCode
{
    #[Override]
    protected function pattern(): string
    {
        return '/^[a-z]{1,3}$/D';
    }
}
