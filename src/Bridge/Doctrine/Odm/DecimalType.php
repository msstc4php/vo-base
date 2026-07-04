<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm;

use MSSTC4PHP\ValueObject\Finance\Decimal;

final class DecimalType extends AbstractStringVoType
{
    public const string NAME = 'vo_decimal';

    protected function valueObjectClass(): string
    {
        return Decimal::class;
    }
}
