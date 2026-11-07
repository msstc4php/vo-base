<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidUrlPathException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * An RFC 3986 path component. The empty string (root-relative) is allowed.
 */
final class UrlPath extends AbstractStringValueObject
{
    #[Override]
    protected function parse(string $value): string
    {
        // Scan for one forbidden character: matching the whole value hits PCRE's JIT stack limit on long paths.
        if (\preg_match('~[^A-Za-z0-9\-._\~!$&\'()*+,;=:@/%]|%(?![0-9A-Fa-f]{2})~', $value) !== 0) {
            throw new InvalidUrlPathException(\sprintf('Value "%s" is not a valid URL path.', InvalidUrlPathException::excerpt($value)));
        }

        return $value;
    }
}
