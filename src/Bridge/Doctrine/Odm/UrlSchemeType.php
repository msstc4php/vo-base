<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm;

use MSSTC4PHP\ValueObject\Network\UrlScheme;

final class UrlSchemeType extends AbstractStringVoType
{
    public const string NAME = 'vo_url_scheme';

    protected function valueObjectClass(): string
    {
        return UrlScheme::class;
    }
}
