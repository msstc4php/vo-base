<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidUrlException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * The http/https subset of a URI: an absolute URL with a non-empty host.
 */
final class Url extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        $parts = \parse_url($value);
        if ($parts === false) {
            throw new InvalidUrlException(\sprintf('Value "%s" is not a valid URL.', $value));
        }

        $scheme = isset($parts['scheme']) ? \strtolower($parts['scheme']) : '';
        if ($scheme !== 'http' && $scheme !== 'https') {
            throw new InvalidUrlException(\sprintf('Value "%s" is not a valid URL.', $value));
        }

        if (!isset($parts['host']) || $parts['host'] === '') {
            throw new InvalidUrlException(\sprintf('Value "%s" is not a valid URL.', $value));
        }

        return $value;
    }

    public function scheme(): string
    {
        return (string) $this->component('scheme');
    }

    public function host(): Hostname
    {
        return Hostname::from((string) $this->component('host'));
    }

    public function port(): ?int
    {
        $port = $this->component('port');

        return $port === null ? null : (int) $port;
    }

    public function path(): string
    {
        $path = $this->component('path');

        return $path === null ? '' : (string) $path;
    }

    /**
     * @param 'scheme'|'host'|'port'|'path' $key
     */
    private function component(string $key): int|string|null
    {
        $parts = \parse_url($this->value);
        if ($parts === false) {
            return null;
        }

        return $parts[$key] ?? null;
    }
}
