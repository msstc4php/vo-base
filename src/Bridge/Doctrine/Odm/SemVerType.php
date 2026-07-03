<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm;

use MSSTC4PHP\ValueObject\Format\SemVer;

final class SemVerType extends AbstractStringVoType
{
    public const string NAME = 'vo_semver';

    protected function valueObjectClass(): string
    {
        return SemVer::class;
    }
}
