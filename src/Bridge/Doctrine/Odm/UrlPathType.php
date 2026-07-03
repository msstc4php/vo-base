<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm;

use MSSTC4PHP\ValueObject\Network\UrlPath;

final class UrlPathType extends AbstractStringVoType
{
    public const string NAME = 'vo_url_path';

    protected function valueObjectClass(): string
    {
        return UrlPath::class;
    }
}
