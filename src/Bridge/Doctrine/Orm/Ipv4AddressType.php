<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm;

use MSSTC4PHP\ValueObject\Network\Ipv4Address;
use Override;

final class Ipv4AddressType extends AbstractStringVoType
{
    public const string NAME = 'vo_ipv4_address';

    protected function valueObjectClass(): string
    {
        return Ipv4Address::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 15;
    }
}
