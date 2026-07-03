<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm;

use MSSTC4PHP\ValueObject\Network\UrlScheme;
use Override;

final class UrlSchemeType extends AbstractStringVoType
{
    public const string NAME = 'vo_url_scheme';

    protected function valueObjectClass(): string
    {
        return UrlScheme::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 32;
    }
}
