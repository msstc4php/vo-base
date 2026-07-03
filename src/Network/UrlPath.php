<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidUrlPathException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * An RFC 3986 path component. The empty string (root-relative) is allowed.
 */
final class UrlPath extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        if (\preg_match('/^(?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\/]|%[0-9A-Fa-f]{2})*$/', $value) !== 1) {
            throw new InvalidUrlPathException(\sprintf('Value "%s" is not a valid URL path.', $value));
        }

        return $value;
    }
}
