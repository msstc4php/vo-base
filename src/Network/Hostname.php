<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidHostnameException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * An RFC 1123 hostname: dot-separated labels of 1-63 chars each, total <= 253,
 * no trailing dot; stored lowercased.
 */
final class Hostname extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        if ($value === '' || \strlen($value) > 253) {
            throw new InvalidHostnameException(\sprintf('Value "%s" is not a valid hostname.', $value));
        }

        if (\preg_match('/^(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)*$/', $value) !== 1) {
            throw new InvalidHostnameException(\sprintf('Value "%s" is not a valid hostname.', $value));
        }

        return \strtolower($value);
    }
}
