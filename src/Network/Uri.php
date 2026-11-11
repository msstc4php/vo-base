<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use MSSTC4PHP\ValueObject\Exception\InvalidUriException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * An RFC 3986 URI that carries an explicit scheme (e.g. https://host/path).
 */
final class Uri extends AbstractStringValueObject
{
    use UriComponents;

    public function scheme(): string
    {
        return (string) $this->component('scheme');
    }

    public function host(): ?string
    {
        $host = $this->component('host');

        return $host === null ? null : (string) $host;
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

    public function query(): ?string
    {
        $query = $this->component('query');

        return $query === null ? null : (string) $query;
    }

    public function fragment(): ?string
    {
        $fragment = $this->component('fragment');

        return $fragment === null ? null : (string) $fragment;
    }

    #[Override]
    protected function parse(string $value): string
    {
        // parse_url() lets spaces and control characters through; a URI never contains them (RFC 3986 section 2).
        $parts = self::hasForbiddenCharacter($value) ? false : \parse_url($value);
        if ($parts === false || !isset($parts['scheme']) || $parts['scheme'] === '') {
            throw new InvalidUriException(\sprintf('Value "%s" is not a valid URI.', InvalidUriException::excerpt($value)));
        }

        return $value;
    }

    /**
     * @param 'scheme'|'host'|'port'|'path'|'query'|'fragment' $key
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
