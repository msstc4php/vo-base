<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Support;

use RuntimeException;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Languages;

/**
 * Renders the ISO backed-enum source files from symfony/intl and brick/money's ISO 4217 table. The generated
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
        // ISO 4217 minor units come from brick/money's generated table; symfony/intl
        // reports CLDR display digits, which differ for 25 codes (HUF, IDR, IQD, ...).
        // Mixing the two shifts stored amounts by orders of magnitude.
        $isoMinorUnits = $this->isoMinorUnitsByCode();

        // ISO registers codes before CLDR picks them up (XAD), so the case set is the union of both sources.
        $cldrCodes = \array_flip(Currencies::getCurrencyCodes());
        $codes = \array_values(\array_unique([...\array_keys($cldrCodes), ...\array_keys($isoMinorUnits)]));
        \sort($codes);

        $cases = '';
        $displayArms = '';
        $isoArms = '';
        foreach ($codes as $code) {
            $cases .= \sprintf("    case %s = '%s';\n", $code, $code);
            $displayArms .= \sprintf(
                "            self::%s => %d,\n",
                $code,
                isset($cldrCodes[$code]) ? Currencies::getFractionDigits($code) : $isoMinorUnits[$code],
            );
            $isoArms .= \sprintf(
                "            self::%s => %s,\n",
                $code,
                isset($isoMinorUnits[$code]) ? (string) $isoMinorUnits[$code] : 'null',
            );
        }

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace MSSTC4PHP\\ValueObject\\I18n;

            /**
             * ISO 4217 currency code. Generated from symfony/intl and the ISO 4217 table
             * shipped by brick/money — do not edit by hand.
             *
             * @see tools/generate-iso-enums.php
             */
            enum CurrencyCode: string
            {
            {$cases}
                /**
                 * CLDR display fraction digits — how many decimals this currency is
                 * conventionally *formatted* with. Not the same as ISO 4217 minor units:
                 * CLDR reports 0 for HUF, IDR and IQD where ISO says 2, 2 and 3.
                 * Codes CLDR does not know yet fall back to their ISO 4217 minor units.
                 * For monetary arithmetic use {@see self::isoMinorUnits()} instead.
                 *
                 * @return int<0, max>
                 */
                public function displayFractionDigits(): int
                {
                    return match (\$this) {
            {$displayArms}        };
                }

                /**
                 * ISO 4217 minor units — the exponent relating this currency to its minor
                 * unit (2 for USD, 0 for JPY, 3 for IQD). This is the value monetary
                 * arithmetic and money persistence must use.
                 *
                 * Withdrawn codes (ADP, BEF, ATS, ...) carry the minor units ISO 4217
                 * recorded for them. Returns null when the ISO data holds no minor-unit
                 * value: codes outside ISO (CNH) and withdrawn codes published without
                 * one (XEU). No value is invented for them.
                 *
                 * @return int<0, max>|null
                 */
                public function isoMinorUnits(): ?int
                {
                    return match (\$this) {
            {$isoArms}        };
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

    /**
     * Fails on an unknown table format: tolerating one would regenerate every currency with null minor units.
     *
     * @return array<non-empty-string, int<0, max>>
     */
    private function isoMinorUnitsByCode(): array
    {
        $table = require \dirname(__DIR__, 2) . '/vendor/brick/money/data/iso-currencies.php';
        if (!\is_array($table)) {
            throw new RuntimeException('brick/money ISO table is not an array.');
        }

        $minorUnits = [];
        foreach ($table as $code => $entry) {
            if (
                !\is_string($code)
                || \preg_match('/^[A-Z]{3}$/', $code) !== 1
                || !\is_array($entry)
                || !\is_int($entry[3] ?? null)
                || $entry[3] < 0
            ) {
                throw new RuntimeException(\sprintf('Unexpected brick/money ISO table entry "%s".', $code));
            }

            $minorUnits[$code] = $entry[3];
        }

        return $minorUnits;
    }
}
