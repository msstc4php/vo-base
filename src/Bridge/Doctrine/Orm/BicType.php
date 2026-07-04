<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm;

use MSSTC4PHP\ValueObject\Finance\Bic;
use Override;

final class BicType extends AbstractStringVoType
{
    public const string NAME = 'vo_bic';

    protected function valueObjectClass(): string
    {
        return Bic::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 11;
    }
}
