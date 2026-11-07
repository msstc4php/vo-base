<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\InvalidQueryStringException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * The query component of a URI, without the leading "?" (e.g. "a=1&b=x%20y"):
 * RFC 3986 characters plus raw "[" and "]".
 *
 * Unlike parse_str() it keeps every pair in order, duplicates included, and
 * never rewrites names ("a.b" stays "a.b", "a[]" stays "a[]"). Pairs are split
 * on "&" and decoded as application/x-www-form-urlencoded ("+" is a space).
 * with*() and without() copy untouched segments byte for byte (empty segments
 * such as "&&" are dropped) and encode only the pairs they add, with RFC 3986
 * percent-encoding.
 *
 * equals() compares the canonical string: percent-encoding hex is upper-cased
 * and encoded unreserved characters are decoded (RFC 3986 section 6.2.2), but
 * "a=x+y" and "a=x%20y" stay different strings with equal parameters().
 */
final class QueryString extends AbstractStringValueObject
{
    // RFC 3986 section 3.4 (pchar / "/" / "?") plus raw "[" and "]": PHP-style "a[]=1" is everywhere and the
    // WHATWG URL standard accepts it. Scanning for one forbidden character, rather than matching the whole value,
    // keeps long queries clear of PCRE's JIT stack and recursion limits.
    private const string FORBIDDEN = '~[^A-Za-z0-9\-._\~!$&\'()*+,;=:@/?\[\]%]|%(?![0-9A-Fa-f]{2})~';

    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    public static function fromParameters(QueryParameter ...$parameters): self
    {
        return self::from(\implode('&', \array_map(self::encode(...), \array_values($parameters))));
    }

    /**
     * Percent-encodes what a QueryString cannot hold (non-ASCII, "{", "|", a "%"
     * without two hex digits, ...) instead of rejecting it; used to read the
     * query of a URI that parse_url() accepted.
     *
     * @internal
     */
    public static function fromLoose(string $query): self
    {
        $encoded = \preg_replace_callback(self::FORBIDDEN, static fn (array $match): string => \rawurlencode($match[0]), $query);

        return self::from($encoded ?? throw new LogicException(\sprintf('Query encoding failed: %s.', \preg_last_error_msg())));
    }

    public static function empty(): self
    {
        return self::from('');
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    /**
     * Every pair in order, duplicates included.
     *
     * @return list<QueryParameter>
     */
    public function parameters(): array
    {
        return \array_map(self::decode(...), $this->segments());
    }

    /**
     * Distinct names in order of first appearance.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return \array_values(\array_unique(\array_map(
            static fn (QueryParameter $parameter): string => $parameter->name,
            $this->parameters(),
        )));
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    /**
     * The first value for the name, or null when it is absent. Names are case-sensitive.
     */
    public function get(string $name): ?string
    {
        foreach ($this->parameters() as $parameter) {
            if ($parameter->name === $name) {
                return $parameter->value;
            }
        }

        return null;
    }

    /**
     * Every value for the name, in order.
     *
     * @return list<string>
     */
    public function getAll(string $name): array
    {
        $values = [];
        foreach ($this->parameters() as $parameter) {
            if ($parameter->name === $name) {
                $values[] = $parameter->value;
            }
        }

        return $values;
    }

    /**
     * A copy where the name has exactly this one value, at the position of its
     * first occurrence (appended when absent).
     */
    public function with(string $name, string $value): self
    {
        $replacement = self::encode(new QueryParameter($name, $value));
        $result = [];
        $placed = false;
        foreach ($this->segments() as $segment) {
            if (self::decode($segment)->name !== $name) {
                $result[] = $segment;
            } elseif (!$placed) {
                $result[] = $replacement;
                $placed = true;
            }
        }

        if (!$placed) {
            $result[] = $replacement;
        }

        return self::from(\implode('&', $result));
    }

    /**
     * A copy with one more pair at the end; existing values for the name stay.
     */
    public function withAdded(string $name, string $value): self
    {
        return self::from(\implode('&', [...$this->segments(), self::encode(new QueryParameter($name, $value))]));
    }

    /**
     * A copy without any pair of that name.
     */
    public function without(string $name): self
    {
        if (!$this->has($name)) {
            return $this;
        }

        return self::from(\implode('&', \array_filter(
            $this->segments(),
            static fn (string $segment): bool => self::decode($segment)->name !== $name,
        )));
    }

    #[Override]
    protected function parse(string $value): string
    {
        $forbidden = \preg_match(self::FORBIDDEN, $value);
        if ($forbidden === false) {
            throw new LogicException(\sprintf('Query validation failed: %s.', \preg_last_error_msg()));
        }

        if ($forbidden === 1) {
            throw new InvalidQueryStringException(\sprintf('Value "%s" is not a valid RFC 3986 query.', InvalidQueryStringException::excerpt($value)));
        }

        $canonical = \preg_replace_callback(
            '~%[0-9A-Fa-f]{2}~',
            static function (array $match): string {
                $character = \rawurldecode($match[0]);

                return \preg_match('~^[A-Za-z0-9\-._\~]$~D', $character) === 1 ? $character : \strtoupper($match[0]);
            },
            $value,
        );

        return $canonical ?? throw new LogicException(\sprintf('Query canonicalization failed: %s.', \preg_last_error_msg()));
    }

    /**
     * The non-empty "&"-separated segments, still encoded.
     *
     * @return list<string>
     */
    private function segments(): array
    {
        return \array_values(\array_filter(\explode('&', $this->value), static fn (string $segment): bool => $segment !== ''));
    }

    private static function decode(string $segment): QueryParameter
    {
        [$name, $value] = \explode('=', $segment, 2) + [1 => ''];

        return new QueryParameter(\urldecode($name), \urldecode($value));
    }

    private static function encode(QueryParameter $parameter): string
    {
        return \rawurlencode($parameter->name) . '=' . \rawurlencode($parameter->value);
    }
}
