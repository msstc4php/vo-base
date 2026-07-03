<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidPhoneNumberException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * Phone number in E.164 form: a leading "+" followed by up to 15 digits.
 */
final class PhoneNumber extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        if (\preg_match('/^\+[1-9]\d{1,14}$/', $value) !== 1) {
            throw new InvalidPhoneNumberException(\sprintf('Invalid E.164 phone number: "%s".', $value));
        }

        return $value;
    }
}
