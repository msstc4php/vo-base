<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bridge\Doctrine\Odm;

use MSSTC4PHP\ValueObject\Identity\EmailAddress;

final class EmailAddressType extends AbstractStringVoType
{
    public const string NAME = 'vo_email_address';

    protected function valueObjectClass(): string
    {
        return EmailAddress::class;
    }
}
