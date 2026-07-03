<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\I18n;

use MSSTC4PHP\ValueObject\Exception\InvalidLocaleException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;

/**
 * BCP 47 locale limited to language and optional region: `en`, `en-US`.
 * The language must be a valid ISO 639-1 code and the region a valid ISO 3166-1 alpha-2 code.
 */
final class Locale extends AbstractStringValueObject
{
    protected function parse(string $value): string
    {
        $parts = \explode('-', $value);
        $language = LanguageCode::tryFrom(\strtolower($parts[0]));

        if ($language === null || \count($parts) > 2) {
            throw new InvalidLocaleException(\sprintf('Invalid locale "%s".', $value));
        }

        if (\count($parts) === 1) {
            return $language->value;
        }

        $region = CountryCode::tryFrom(\strtoupper($parts[1]));

        if ($region === null) {
            throw new InvalidLocaleException(\sprintf('Invalid locale "%s".', $value));
        }

        return $language->value . '-' . $region->value;
    }

    public function language(): LanguageCode
    {
        return LanguageCode::from(\explode('-', $this->value)[0]);
    }

    public function region(): ?CountryCode
    {
        $parts = \explode('-', $this->value);

        return isset($parts[1]) ? CountryCode::from($parts[1]) : null;
    }
}
