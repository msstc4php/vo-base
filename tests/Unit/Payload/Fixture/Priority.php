<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Payload\Fixture;

enum Priority: int
{
    case Low = 1;
    case High = 2;
}
