<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm;

use MSSTC4PHP\ValueObject\Format\SemVer;
use Override;

final class SemVerType extends AbstractStringVoType
{
    public const string NAME = 'vo_semver';

    protected function valueObjectClass(): string
    {
        return SemVer::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 255;
    }
}
