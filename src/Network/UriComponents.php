<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

/**
 * Immutable component mutators and query parsing for string-backed URL/URI
 * value objects. Every {@see with*()} rebuilds the string form and passes it
 * back through the concrete type's validating constructor, so an invalid
 * combination throws that type's exception rather than producing a bad value.
 */
trait UriComponents
{
    // Space, the C0 controls and DEL: never part of a URI (RFC 3986 section 2), yet parse_url() accepts them.
    private const string FORBIDDEN_CHARACTERS = " \x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F"
        . "\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x7F";

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
     * The query as a {@see QueryString}, or null when there is no "?". A bare
     * "?" yields an empty QueryString; writing an empty one back drops the "?".
     *
     * The URI may carry characters a QueryString does not (non-ASCII, "{", "|",
     * a "%" without two hex digits, ...); they are percent-encoded on the way,
     * so withQueryString($uri->queryString()) need not return the same string.
     */
    public function queryString(): ?QueryString
    {
        $query = $this->rawQuery();
        if ($query === null) {
            return null;
        }

        return QueryString::fromLoose($query);
    }

    public function withQueryString(QueryString $query): static
    {
        return $this->withQuery($query->value);
    }

    /**
     * The decoded query parameters. Array-style keys (e.g. "a[]=1&a[]=2")
     * decode to nested arrays, matching {@see parse_str()}.
     *
     * @return array<array-key, string|array<array-key, mixed>>
     */
    public function queryParameters(): array
    {
        $query = $this->rawQuery();
        if ($query === null || $query === '') {
            return [];
        }

        parse_str($query, $result);

        return $result;
    }

    private static function hasForbiddenCharacter(string $value): bool
    {
        return \strpbrk($value, self::FORBIDDEN_CHARACTERS) !== false;
    }

    private function rawQuery(): ?string
    {
        $parts = parse_url($this->value);

        return $parts === false ? null : ($parts['query'] ?? null);
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
