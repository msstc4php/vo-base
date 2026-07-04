<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm;

use MSSTC4PHP\ValueObject\Format\Base64String;

final class Base64StringType extends AbstractStringVoType
{
    public const string NAME = 'vo_base64_string';

    protected function valueObjectClass(): string
    {
        return Base64String::class;
    }
}
