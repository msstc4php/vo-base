<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

/**
 * Immutable component mutators and query parsing for string-backed URL/URI
 * value objects. Every {@see with*()} rebuilds the string form and passes it
 * back through the concrete type's validating constructor, so an invalid
 * combination throws that type's exception rather than producing a bad value.
 */
trait UriComponents
{
    public function withScheme(string $scheme): static
    {
        return $this->withComponent('scheme', $scheme);
    }

    public function withHost(string $host): static
    {
        return $this->withComponent('host', $host);
    }

    public function withPort(int $port): static
    {
        return $this->withComponent('port', $port);
    }

    public function withPath(string $path): static
    {
        return $this->withComponent('path', $path);
    }

    public function withQuery(string $query): static
    {
        return $this->withComponent('query', $query === '' ? null : $query);
    }

    public function withFragment(string $fragment): static
    {
        return $this->withComponent('fragment', $fragment === '' ? null : $fragment);
    }

    /**
     * The decoded query parameters. Array-style keys (e.g. "a[]=1&a[]=2")
     * decode to nested arrays, matching {@see parse_str()}.
     *
     * @return array<array-key, string|array<array-key, mixed>>
     */
    public function queryParameters(): array
    {
        $parts = parse_url($this->value);
        $query = $parts === false ? null : ($parts['query'] ?? null);
        if ($query === null || $query === '') {
            return [];
        }

        parse_str($query, $result);

        return $result;
    }

    /**
     * @param 'scheme'|'host'|'port'|'path'|'query'|'fragment' $key
     */
    private function withComponent(string $key, int|string|null $value): static
    {
        $parts = parse_url($this->value);
        $components = $parts === false ? [] : $parts;

        if ($value === null) {
            unset($components[$key]);
        } else {
            $components[$key] = $value;
        }

        return new static($this->assembleUri($components));
    }

    /**
     * @param array<string, int|string> $components
     */
    private function assembleUri(array $components): string
    {
        $result = '';

        if (isset($components['scheme'])) {
            $result .= $components['scheme'] . ':';
        }

        if (isset($components['host'])) {
            $result .= '//';
            if (isset($components['user'])) {
                $result .= $components['user'];
                if (isset($components['pass'])) {
                    $result .= ':' . $components['pass'];
                }
                $result .= '@';
            }
            $result .= $components['host'];
            if (isset($components['port'])) {
                $result .= ':' . $components['port'];
            }
        }

        if (isset($components['path'])) {
            $result .= $components['path'];
        }

        if (isset($components['query'])) {
            $result .= '?' . $components['query'];
        }

        if (isset($components['fragment'])) {
            $result .= '#' . $components['fragment'];
        }

        return $result;
    }
}
