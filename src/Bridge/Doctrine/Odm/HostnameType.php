<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm;

use MSSTC4PHP\ValueObject\Network\Hostname;

final class HostnameType extends AbstractStringVoType
{
    public const string NAME = 'vo_hostname';

    protected function valueObjectClass(): string
    {
        return Hostname::class;
    }
}
