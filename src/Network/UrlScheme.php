<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidUrlSchemeException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * A URI scheme name (e.g. http, https, ftp), stored lowercased.
 */
final class UrlScheme extends AbstractStringValueObject
{
    #[Override]
    protected function parse(string $value): string
    {
        if (\preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*$/D', $value) !== 1) {
            throw new InvalidUrlSchemeException(\sprintf('Value "%s" is not a valid URL scheme.', InvalidUrlSchemeException::excerpt($value)));
        }

        return \strtolower($value);
    }
}
