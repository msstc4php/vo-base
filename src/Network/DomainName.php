<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidDomainNameException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use MSSTC4PHP\ValueObject\Support\DottedLabels;

/**
 * A registrable domain name: an RFC 1123 hostname with at least two labels and
 * an alphabetic TLD of length >= 2; stored lowercased.
 */
final class DomainName extends AbstractStringValueObject
{
    use DottedLabels;

    protected function parse(string $value): string
    {
        if ($value === '' || \strlen($value) > 253) {
            throw new InvalidDomainNameException(\sprintf('Value "%s" is not a valid domain name.', $value));
        }

        if (\preg_match('/^(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)*$/', $value) !== 1) {
            throw new InvalidDomainNameException(\sprintf('Value "%s" is not a valid domain name.', $value));
        }

        $labels = \explode('.', $value);
        if (\count($labels) < 2) {
            throw new InvalidDomainNameException(\sprintf('Value "%s" is not a valid domain name.', $value));
        }

        $tld = $labels[\count($labels) - 1];
        if (\preg_match('/^[A-Za-z]{2,}$/', $tld) !== 1) {
            throw new InvalidDomainNameException(\sprintf('Value "%s" is not a valid domain name.', $value));
        }

        return \strtolower($value);
    }
}
