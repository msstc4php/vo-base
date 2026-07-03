<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidUrlSchemeException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * A URI scheme name (e.g. http, https, ftp), stored lowercased.
 */
final class UrlScheme extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        if (\preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*$/', $value) !== 1) {
            throw new InvalidUrlSchemeException(\sprintf('Value "%s" is not a valid URL scheme.', $value));
        }

        return \strtolower($value);
    }
}
