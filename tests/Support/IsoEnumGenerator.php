<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Support;

use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Languages;

/**
 * Renders the ISO backed-enum source files from symfony/intl. The generated
 * output is committed so the runtime never depends on symfony/intl; this class
 * is used both by tools/generate-iso-enums.php and by the up-to-date guard test.
 */
final class IsoEnumGenerator
{
    /**
     * @return array<string, string> map of target relative path => file content
     */
    public function all(): array
    {
        return [
            'src/I18n/CountryCode.php' => $this->countryCode(),
            'src/I18n/CurrencyCode.php' => $this->currencyCode(),
            'src/I18n/LanguageCode.php' => $this->languageCode(),
        ];
    }

    public function countryCode(): string
    {
        /** @var list<array{code: string, alpha3: string, numeric: string}> $rows */
        $rows = [];
        foreach (Countries::getCountryCodes() as $alpha2) {
            try {
                $alpha3 = Countries::getAlpha3Code($alpha2);
                $numeric = \str_pad(Countries::getNumericCode($alpha2), 3, '0', STR_PAD_LEFT);
            } catch (MissingResourceException) {
                continue;
            }

            $rows[] = ['code' => $alpha2, 'alpha3' => $alpha3, 'numeric' => $numeric];
        }

        $cases = '';
        $alpha3Arms = '';
        $numericArms = '';
        $fromAlpha3Arms = '';
        $fromNumericArms = '';
        foreach ($rows as $row) {
            $cases .= \sprintf("    case %s = '%s';\n", $row['code'], $row['code']);
            $alpha3Arms .= \sprintf("            self::%s => '%s',\n", $row['code'], $row['alpha3']);
            $numericArms .= \sprintf("            self::%s => '%s',\n", $row['code'], $row['numeric']);
            $fromAlpha3Arms .= \sprintf("            '%s' => self::%s,\n", $row['alpha3'], $row['code']);
            $fromNumericArms .= \sprintf("            '%s' => self::%s,\n", $row['numeric'], $row['code']);
        }

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace MSSTC4PHP\\ValueObject\\I18n;

            use MSSTC4PHP\\ValueObject\\Exception\\InvalidCountryCodeException;

            /**
             * ISO 3166-1 country code (alpha-2). Generated from symfony/intl — do not edit by hand.
             *
             * @see tools/generate-iso-enums.php
             */
            enum CountryCode: string
            {
            {$cases}
                /** @return non-empty-string ISO 3166-1 alpha-3 code */
                public function alpha3(): string
                {
                    return match (\$this) {
            {$alpha3Arms}        };
                }

                /** @return non-empty-string ISO 3166-1 numeric code (zero-padded) */
                public function numeric(): string
                {
                    return match (\$this) {
            {$numericArms}        };
                }

                public static function fromAlpha3(string \$alpha3): self
                {
                    return match (strtoupper(\$alpha3)) {
            {$fromAlpha3Arms}            default => throw new InvalidCountryCodeException(sprintf('Unknown ISO 3166-1 alpha-3 code "%s".', \$alpha3)),
                    };
                }

                public static function fromNumeric(string \$numeric): self
                {
                    return match (str_pad(\$numeric, 3, '0', STR_PAD_LEFT)) {
            {$fromNumericArms}            default => throw new InvalidCountryCodeException(sprintf('Unknown ISO 3166-1 numeric code "%s".', \$numeric)),
                    };
                }
            }

            PHP;
    }

    public function currencyCode(): string
    {
        $cases = '';
        $minorArms = '';
        foreach (Currencies::getCurrencyCodes() as $code) {
            $cases .= \sprintf("    case %s = '%s';\n", $code, $code);
            $minorArms .= \sprintf("            self::%s => %d,\n", $code, Currencies::getFractionDigits($code));
        }

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace MSSTC4PHP\\ValueObject\\I18n;

            /**
             * ISO 4217 currency code. Generated from symfony/intl — do not edit by hand.
             *
             * @see tools/generate-iso-enums.php
             */
            enum CurrencyCode: string
            {
            {$cases}
                /** @return int<0, max> number of minor-unit digits (e.g. 2 for USD, 0 for JPY) */
                public function minorUnits(): int
                {
                    return match (\$this) {
            {$minorArms}        };
                }
            }

            PHP;
    }

    public function languageCode(): string
    {
        $cases = '';
        foreach (Languages::getLanguageCodes() as $code) {
            if (\strlen($code) !== 2) {
                continue; // ISO 639-1 is the two-letter set
            }

            $cases .= \sprintf("    case %s = '%s';\n", \strtoupper($code), $code);
        }

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace MSSTC4PHP\\ValueObject\\I18n;

            /**
             * ISO 639-1 language code (two-letter). Generated from symfony/intl — do not edit by hand.
             *
             * @see tools/generate-iso-enums.php
             */
            enum LanguageCode: string
            {
            {$cases}}

            PHP;
    }
}
