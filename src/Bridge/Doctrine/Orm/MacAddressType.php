<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Orm;

use MSSTC4PHP\ValueObject\Network\MacAddress;
use Override;

final class MacAddressType extends AbstractStringVoType
{
    public const string NAME = 'vo_mac_address';

    protected function valueObjectClass(): string
    {
        return MacAddress::class;
    }

    #[Override]
    protected function columnLength(): int
    {
        return 17;
    }
}
