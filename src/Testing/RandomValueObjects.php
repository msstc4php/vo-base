<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Testing;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use MSSTC4PHP\ValueObject\Finance\Bic;
use MSSTC4PHP\ValueObject\Finance\CreditCardNumber;
use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Finance\Iban;
use MSSTC4PHP\ValueObject\Format\Base64String;
use MSSTC4PHP\ValueObject\Format\HexColor;
use MSSTC4PHP\ValueObject\Format\JsonString;
use MSSTC4PHP\ValueObject\Format\PhoneNumber;
use MSSTC4PHP\ValueObject\Format\SemVer;
use MSSTC4PHP\ValueObject\Format\Slug;
use MSSTC4PHP\ValueObject\Geo\GeoCoordinates;
use MSSTC4PHP\ValueObject\Geo\Latitude;
use MSSTC4PHP\ValueObject\Geo\Longitude;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;
use MSSTC4PHP\ValueObject\I18n\LanguageCode;
use MSSTC4PHP\ValueObject\I18n\Locale;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Identity\Uuid;
use MSSTC4PHP\ValueObject\Network\Cidr;
use MSSTC4PHP\ValueObject\Network\DomainName;
use MSSTC4PHP\ValueObject\Network\Hostname;
use MSSTC4PHP\ValueObject\Network\HttpMethod;
use MSSTC4PHP\ValueObject\Network\IpAddress;
use MSSTC4PHP\ValueObject\Network\IpRange;
use MSSTC4PHP\ValueObject\Network\Ipv4Address;
use MSSTC4PHP\ValueObject\Network\Ipv6Address;
use MSSTC4PHP\ValueObject\Network\MacAddress;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Network\QueryParameter;
use MSSTC4PHP\ValueObject\Network\QueryString;
use MSSTC4PHP\ValueObject\Network\Uri;
use MSSTC4PHP\ValueObject\Network\Url;
use MSSTC4PHP\ValueObject\Network\UrlPath;
use MSSTC4PHP\ValueObject\Network\UrlScheme;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use MSSTC4PHP\ValueObject\Primitive\NonEmptyString;
use MSSTC4PHP\ValueObject\Primitive\NonNegativeInt;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use MSSTC4PHP\ValueObject\Primitive\PositiveInt;
use MSSTC4PHP\ValueObject\Support\PackedAddress;
use MSSTC4PHP\ValueObject\Temporal\DateRange;
use MSSTC4PHP\ValueObject\Temporal\Duration;
use MSSTC4PHP\ValueObject\Temporal\TimeOfDay;
use Random\Engine\Secure;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Valid random value objects for consumers' tests and property-style checks.
 * Pass a seed to make a run reproducible within one library version (a new
 * release may draw differently), or a Randomizer of your own; without either
 * the engine is cryptographically secure and every run differs. The set of
 * generators and their return types follow semver; the values a seed draws do not.
 * Every value goes through the type's validating constructor, so a generator
 * bug surfaces as an exception, never as an invalid instance.
 *
 * Mutation testing skips this class: shifting a random range bound by one is
 * not observable in a bounded number of draws. The tests assert validity and
 * the shape of every generator instead.
 *
 * @infection-ignore-all
 */
final readonly class RandomValueObjects
{
    private const string LOWER_ALNUM = 'abcdefghijklmnopqrstuvwxyz0123456789';

    private const string LOWER = 'abcdefghijklmnopqrstuvwxyz';

    private Randomizer $random;

    /**
     * A Randomizer passed in is advanced by every draw, so sharing it with other code shifts both sequences.
     */
    public function __construct(int|Randomizer|null $seed = null)
    {
        $this->random = $seed instanceof Randomizer
            ? $seed
            : new Randomizer($seed === null ? new Secure() : new Xoshiro256StarStar($seed));
    }

    public function ipv4Address(): Ipv4Address
    {
        return Ipv4Address::from(PackedAddress::unpack($this->random->getBytes(4)));
    }

    public function ipv6Address(): Ipv6Address
    {
        return Ipv6Address::from(PackedAddress::unpack($this->random->getBytes(16)));
    }

    public function ipAddress(): IpAddress
    {
        return IpAddress::fromString($this->random->getInt(0, 1) === 0 ? $this->ipv4Address()->value : $this->ipv6Address()->value);
    }

    public function cidr(): Cidr
    {
        $address = $this->ipAddress();
        $maxPrefix = $address->version() === 4 ? 32 : 128;

        return Cidr::from(\sprintf('%s/%d', $address->value, $this->random->getInt(0, $maxPrefix)));
    }

    public function ipRange(): IpRange
    {
        $bytes = $this->random->getInt(0, 1) === 0 ? 4 : 16;
        $a = $this->random->getBytes($bytes);
        $b = $this->random->getBytes($bytes);
        [$first, $last] = \strcmp($a, $b) <= 0 ? [$a, $b] : [$b, $a];

        return IpRange::from(PackedAddress::unpack($first) . '-' . PackedAddress::unpack($last));
    }

    public function port(): Port
    {
        return Port::from($this->random->getInt(0, 65535));
    }

    public function macAddress(): MacAddress
    {
        return MacAddress::from(\implode(':', \str_split(\bin2hex($this->random->getBytes(6)), 2)));
    }

    /**
     * The last label starts with a letter: an all-digit host such as "42" is a
     * valid Hostname, yet URL parsers read it as an IPv4 address.
     */
    public function hostname(): Hostname
    {
        return Hostname::from($this->labels($this->random->getInt(1, 3)));
    }

    public function domainName(): DomainName
    {
        return DomainName::from($this->labels($this->random->getInt(1, 2)) . '.' . $this->word(2, 6, self::LOWER));
    }

    public function emailAddress(): EmailAddress
    {
        $local = $this->word(1, 12, self::LOWER_ALNUM);
        if ($this->random->getInt(0, 2) === 0) {
            $local .= ($this->random->getInt(0, 1) === 0 ? '.' : '+') . $this->word(1, 6, self::LOWER_ALNUM);
        }

        return EmailAddress::from($local . '@' . $this->domainName()->value);
    }

    public function url(): Url
    {
        return Url::from(\sprintf(
            '%s://%s%s/%s%s%s',
            $this->random->getInt(0, 1) === 0 ? 'http' : 'https',
            $this->domainName()->value,
            $this->random->getInt(0, 3) === 0 ? ':' . $this->random->getInt(1, 65535) : '',
            $this->word(0, 12, self::LOWER_ALNUM),
            $this->random->getInt(0, 2) === 0 ? '?' . $this->queryString()->value : '',
            $this->random->getInt(0, 4) === 0 ? '#' . $this->word(1, 8, self::LOWER_ALNUM) : '',
        ));
    }

    public function uri(): Uri
    {
        return Uri::from($this->random->getInt(0, 1) === 0
            ? $this->url()->value
            : 'mailto:' . $this->emailAddress()->value);
    }

    public function urlScheme(): UrlScheme
    {
        return UrlScheme::from($this->word(1, 1, self::LOWER) . $this->word(0, 8, self::LOWER_ALNUM . '+.-'));
    }

    public function urlPath(): UrlPath
    {
        $segments = [];
        for ($i = $this->random->getInt(0, 4); $i > 0; $i--) {
            $segments[] = \rawurlencode($this->word(1, 8, self::LOWER_ALNUM . ' _~'));
        }

        return UrlPath::from('/' . \implode('/', $segments));
    }

    public function queryString(): QueryString
    {
        $parameters = [];
        for ($i = $this->random->getInt(0, 4); $i > 0; $i--) {
            $parameters[] = new QueryParameter($this->word(1, 6, self::LOWER_ALNUM), $this->word(0, 8, self::LOWER_ALNUM . ' &=+'));
        }

        return QueryString::fromParameters(...$parameters);
    }

    public function httpMethod(): HttpMethod
    {
        $cases = HttpMethod::cases();

        return $cases[$this->random->getInt(0, \count($cases) - 1)];
    }

    public function phoneNumber(): PhoneNumber
    {
        return PhoneNumber::from('+' . $this->random->getInt(1, 9) . $this->digits($this->random->getInt(6, 13)));
    }

    public function decimal(): Decimal
    {
        $integer = (string) $this->random->getInt(0, 1_000_000);
        $fraction = $this->random->getInt(0, 1) === 0 ? '' : '.' . $this->digits($this->random->getInt(1, 8));

        return Decimal::from(($this->random->getInt(0, 3) === 0 ? '-' : '') . $integer . $fraction);
    }

    public function locale(): Locale
    {
        $language = $this->languageCode()->value;

        return Locale::from($this->random->getInt(0, 1) === 0 ? $language : $language . '-' . $this->countryCode()->value);
    }

    public function base64String(): Base64String
    {
        return Base64String::from(\base64_encode($this->random->getBytes($this->random->getInt(1, 48))));
    }

    public function jsonString(): JsonString
    {
        return JsonString::from(\json_encode(
            ['id' => $this->random->getInt(1, 1_000_000), 'name' => $this->word(1, 12, self::LOWER_ALNUM), 'active' => $this->random->getInt(0, 1) === 1],
            \JSON_THROW_ON_ERROR,
        ));
    }

    public function intRange(): IntRange
    {
        $a = $this->random->getInt(-1_000_000, 1_000_000);
        $b = $this->random->getInt(-1_000_000, 1_000_000);

        return new IntRange(\min($a, $b), \max($a, $b));
    }

    public function boundedString(): BoundedString
    {
        $value = $this->word(0, 24, self::LOWER_ALNUM);
        $length = \strlen($value);

        return new BoundedString($value, $this->random->getInt(0, $length), $this->random->getInt(0, 1) === 0 ? null : $this->random->getInt($length, $length + 16));
    }

    public function uuid(): Uuid
    {
        $bytes = $this->random->getBytes(16);
        $bytes[6] = \chr((\ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = \chr((\ord($bytes[8]) & 0x3F) | 0x80);
        $hex = \bin2hex($bytes);

        return Uuid::from(\sprintf(
            '%s-%s-%s-%s-%s',
            \substr($hex, 0, 8),
            \substr($hex, 8, 4),
            \substr($hex, 12, 4),
            \substr($hex, 16, 4),
            \substr($hex, 20),
        ));
    }

    public function slug(): Slug
    {
        $parts = [];
        for ($i = $this->random->getInt(1, 4); $i > 0; $i--) {
            $parts[] = $this->word(1, 8, self::LOWER_ALNUM);
        }

        return Slug::from(\implode('-', $parts));
    }

    public function hexColor(): HexColor
    {
        return HexColor::from('#' . \bin2hex($this->random->getBytes(3)));
    }

    public function semVer(): SemVer
    {
        return SemVer::from(\sprintf(
            '%d.%d.%d%s%s',
            $this->random->getInt(0, 20),
            $this->random->getInt(0, 50),
            $this->random->getInt(0, 100),
            $this->random->getInt(0, 3) === 0 ? '-' . ['alpha', 'beta', 'rc'][$this->random->getInt(0, 2)] . '.' . $this->random->getInt(1, 9) : '',
            $this->random->getInt(0, 5) === 0 ? '+' . $this->word(1, 8, self::LOWER_ALNUM) : '',
        ));
    }

    public function nonEmptyString(): NonEmptyString
    {
        return NonEmptyString::from($this->word(1, 24, self::LOWER_ALNUM));
    }

    public function positiveInt(): PositiveInt
    {
        return PositiveInt::from($this->random->getInt(1, \PHP_INT_MAX));
    }

    public function nonNegativeInt(): NonNegativeInt
    {
        return NonNegativeInt::from($this->random->getInt(0, \PHP_INT_MAX));
    }

    public function percentage(): Percentage
    {
        return Percentage::from($this->random->getInt(0, 10_000) / 100);
    }

    public function latitude(): Latitude
    {
        return Latitude::from($this->random->getInt(-90_000_000, 90_000_000) / 1_000_000);
    }

    public function longitude(): Longitude
    {
        return Longitude::from($this->random->getInt(-180_000_000, 180_000_000) / 1_000_000);
    }

    public function geoCoordinates(): GeoCoordinates
    {
        return GeoCoordinates::fromFloats($this->latitude()->value, $this->longitude()->value);
    }

    public function countryCode(): CountryCode
    {
        $cases = CountryCode::cases();

        return $cases[$this->random->getInt(0, \count($cases) - 1)];
    }

    /**
     * A currency with ISO 4217 minor units, i.e. one that can be mapped as money.
     */
    public function currencyCode(): CurrencyCode
    {
        $cases = \array_values(\array_filter(
            CurrencyCode::cases(),
            static fn (CurrencyCode $currency): bool => $currency->isoMinorUnits() !== null,
        ));

        return $cases[$this->random->getInt(0, \count($cases) - 1)];
    }

    public function languageCode(): LanguageCode
    {
        $cases = LanguageCode::cases();

        return $cases[$this->random->getInt(0, \count($cases) - 1)];
    }

    public function timeOfDay(): TimeOfDay
    {
        return TimeOfDay::from(\sprintf(
            '%02d:%02d:%02d',
            $this->random->getInt(0, 23),
            $this->random->getInt(0, 59),
            $this->random->getInt(0, 59),
        ));
    }

    public function duration(): Duration
    {
        return Duration::fromSeconds($this->random->getInt(0, 10 * 365 * 86_400));
    }

    /**
     * A UTC range of up to a year, starting between 2020 and 2030.
     */
    public function dateRange(): DateRange
    {
        $start = new DateTimeImmutable('@' . $this->random->getInt(1_577_836_800, 1_893_456_000))
            ->setTimezone(new DateTimeZone('UTC'))
        ;

        return new DateRange($start, $start->add(new DateInterval(\sprintf('PT%dS', $this->random->getInt(0, 365 * 86_400)))));
    }

    /**
     * A German IBAN (DE, 18 digits) with a valid ISO 7064 check.
     */
    public function iban(): Iban
    {
        $bban = $this->digits(18);
        // "DE00" moved to the end with letters as numbers (D=13, E=14), per ISO 7064 MOD 97-10.
        $check = 98 - $this->mod97($bban . '131400');

        return Iban::from(\sprintf('DE%02d%s', $check, $bban));
    }

    /**
     * A German BIC. The location code's second character is never "0" (ISO
     * 9362 marks test BICs that way) nor "O".
     */
    public function bic(): Bic
    {
        return Bic::from(\strtoupper(
            $this->word(4, 4, self::LOWER) . 'DE' . $this->word(1, 1, self::LOWER_ALNUM) . $this->word(1, 1, 'abcdefghijklmnpqrstuvwxyz123456789'),
        ));
    }

    /**
     * A 16-digit Visa-style number (prefix 4) with a valid Luhn check digit.
     */
    public function creditCardNumber(): CreditCardNumber
    {
        $body = '4' . $this->digits(14);
        $sum = 0;
        foreach (\str_split(\strrev($body)) as $index => $digit) {
            $value = (int) $digit * ($index % 2 === 0 ? 2 : 1);
            $sum += $value > 9 ? $value - 9 : $value;
        }

        return CreditCardNumber::from($body . ((10 - $sum % 10) % 10));
    }

    /**
     * @param int<1, 3> $count
     */
    private function labels(int $count): string
    {
        $labels = [];
        for ($i = 1; $i < $count; $i++) {
            $labels[] = $this->word(1, 12, self::LOWER_ALNUM);
        }

        $labels[] = $this->word(1, 1, self::LOWER) . $this->word(0, 11, self::LOWER_ALNUM);

        return \implode('.', $labels);
    }

    /**
     * @param int<0, max> $min
     * @param int<0, max> $max
     * @param non-empty-string $alphabet
     */
    private function word(int $min, int $max, string $alphabet): string
    {
        $length = $this->random->getInt($min, $max);

        return $length === 0 ? '' : $this->random->getBytesFromString($alphabet, $length);
    }

    /**
     * @param non-empty-string $digits ASCII digits only
     */
    private function mod97(string $digits): int
    {
        $remainder = 0;
        foreach (\str_split($digits, 7) as $chunk) {
            $remainder = (int) ($remainder . $chunk) % 97;
        }

        return $remainder;
    }

    /**
     * @param positive-int $count
     *
     * @return non-empty-string
     */
    private function digits(int $count): string
    {
        return $this->random->getBytesFromString('0123456789', $count);
    }
}
