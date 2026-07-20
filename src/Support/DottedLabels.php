<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Support;

/**
 * Label decomposition for dot-separated name value objects (hostnames, domain
 * names). Relies on the concrete type storing its canonical (lowercased) value
 * in a public readonly {@see $value} string.
 */
trait DottedLabels
{
    /**
     * The dot-separated labels, most-specific first (e.g. ["www", "example", "com"]).
     *
     * @return non-empty-list<string>
     */
    public function labels(): array
    {
        return explode('.', $this->value);
    }

    /**
     * The right-most label (e.g. "com" for "www.example.com").
     */
    public function tld(): string
    {
        $labels = $this->labels();

        return $labels[\count($labels) - 1];
    }

    /**
     * Whether this name is a strict subdomain of the other, compared on label
     * boundaries (so "evilexample.com" is NOT a subdomain of "example.com").
     */
    public function isSubdomainOf(self $other): bool
    {
        $mine = $this->labels();
        $theirs = $other->labels();

        if (\count($mine) <= \count($theirs)) {
            return false;
        }

        return \array_slice($mine, -\count($theirs)) === $theirs;
    }
}
