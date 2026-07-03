<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm;

use MSSTC4PHP\ValueObject\Network\IpAddress;
use Override;

final class IpAddressType extends AbstractStringVoType
{
    public const string NAME = 'vo_ip_address';

    protected function valueObjectClass(): string
    {
        return IpAddress::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 45;
    }
}
