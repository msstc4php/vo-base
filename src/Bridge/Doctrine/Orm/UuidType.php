<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm;

use MSSTC4PHP\ValueObject\Identity\Uuid;
use Override;

final class UuidType extends AbstractStringVoType
{
    public const string NAME = 'vo_uuid';

    protected function valueObjectClass(): string
    {
        return Uuid::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 36;
    }
}
