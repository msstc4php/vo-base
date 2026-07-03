<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm;

use MSSTC4PHP\ValueObject\Network\Url;
use Override;

final class UrlType extends AbstractStringVoType
{
    public const string NAME = 'vo_url';

    protected function valueObjectClass(): string
    {
        return Url::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 2048;
    }
}
