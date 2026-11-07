<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Testing;

use MSSTC4PHP\ValueObject\Exception\InvalidCountryCodeException;
use MSSTC4PHP\ValueObject\Finance\Bic;
use MSSTC4PHP\ValueObject\Finance\CreditCardNumber;
use MSSTC4PHP\ValueObject\Finance\Decimal;
use MSSTC4PHP\ValueObject\Finance\Iban;
use MSSTC4PHP\ValueObject\Format\Base64String;
use MSSTC4PHP\ValueObject\Format\ClassString;
use MSSTC4PHP\ValueObject\Format\HexColor;
use MSSTC4PHP\ValueObject\Format\PhoneNumber;
use MSSTC4PHP\ValueObject\Format\SemVer;
use MSSTC4PHP\ValueObject\Format\Slug;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\Locale;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Identity\Uuid;
use MSSTC4PHP\ValueObject\Network\Cidr;
use MSSTC4PHP\ValueObject\Network\DomainName;
use MSSTC4PHP\ValueObject\Network\Hostname;
use MSSTC4PHP\ValueObject\Network\IpAddress;
use MSSTC4PHP\ValueObject\Network\IpRange;
use MSSTC4PHP\ValueObject\Network\Ipv4Address;
use MSSTC4PHP\ValueObject\Network\Ipv6Address;
use MSSTC4PHP\ValueObject\Network\MacAddress;
use MSSTC4PHP\ValueObject\Network\QueryString;
use MSSTC4PHP\ValueObject\Network\Uri;
use MSSTC4PHP\ValueObject\Network\Url;
use MSSTC4PHP\ValueObject\Network\UrlPath;
use MSSTC4PHP\ValueObject\Network\UrlScheme;
use MSSTC4PHP\ValueObject\Temporal\Duration;
use MSSTC4PHP\ValueObject\Temporal\TimeOfDay;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A pattern anchored with "$" but without the D modifier, or a check left to
 * parse_url(), accepts a trailing newline, and such a value reaches HTTP
 * headers and logs intact. The list is explicit on purpose, independent of any
 * generator, so a type cannot drop out of the check unnoticed.
 */
#[CoversNothing]
final class ControlCharacterRejectionTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, string, string}>
     */
    public static function invalidProvider(): iterable
    {
        $characters = ['newline' => "\n", 'crlf' => "\r\n", 'nul' => "\0", 'tab' => "\t", 'del' => "\x7F"];

        foreach (self::samples() as [$class, $valid, $rejectsSpace]) {
            $short = \substr((string) \strrchr($class, '\\'), 1);
            $all = $rejectsSpace ? $characters + ['space' => ' '] : $characters;
            foreach ($all as $label => $character) {
                $middle = \intdiv(\strlen($valid), 2);
                yield "{$short} {$label} suffix" => [$class, $valid, $valid . $character];
                yield "{$short} {$label} prefix" => [$class, $valid, $character . $valid];
                yield "{$short} {$label} inside" => [$class, $valid, \substr($valid, 0, $middle) . $character . \substr($valid, $middle)];
            }
        }
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('invalidProvider')]
    public function testRejectsControlCharacters(string $class, string $valid, string $invalid): void
    {
        $isValid = [$class, 'isValid'];
        self::assertIsCallable($isValid);

        self::assertTrue($isValid($valid), $valid);
        self::assertFalse($isValid($invalid), \json_encode($invalid, \JSON_THROW_ON_ERROR));
    }

    public function testCountryCodeLookupsRejectControlCharacters(): void
    {
        self::assertNull(CountryCode::tryFrom("DE\n"));

        try {
            CountryCode::fromAlpha3("DEU\r\n");
            self::fail('expected an exception');
        } catch (InvalidCountryCodeException $exception) {
            self::assertStringNotContainsString("\r\n", $exception->getMessage());
        }
    }

    /**
     * Types that strip grouping spaces on purpose (IBAN, card numbers) skip the space case.
     *
     * @return list<array{class-string, string, bool}>
     */
    private static function samples(): array
    {
        return [
            [Bic::class, 'DEUTDEFF500', true],
            [CreditCardNumber::class, '4111111111111111', false],
            [Decimal::class, '-12.50', true],
            [Iban::class, 'DE89370400440532013000', false],
            [HexColor::class, '#a1b2c3', true],
            [PhoneNumber::class, '+14155552671', true],
            [SemVer::class, '1.2.3-rc.1+build', true],
            [Slug::class, 'a-b-c', true],
            [Uuid::class, '123e4567-e89b-42d3-a456-426614174000', true],
            [DomainName::class, 'example.com', true],
            [Hostname::class, 'host.example', true],
            [MacAddress::class, '00:1a:2b:3c:4d:5e', true],
            [UrlPath::class, '/a/b%20c', true],
            [UrlScheme::class, 'https', true],
            [Duration::class, 'PT1H30M', true],
            [TimeOfDay::class, '10:20:30', true],
            [Url::class, 'https://example.com/p?q=1', true],
            [Uri::class, 'mailto:user@example.com', true],
            [QueryString::class, 'a=1&b=2', true],
            [Cidr::class, '10.0.0.0/8', true],
            [IpRange::class, '10.0.0.1-10.0.0.9', true],
            [EmailAddress::class, 'user@example.com', true],
            [Locale::class, 'en-US', true],
            [IpAddress::class, '2001:db8::1', true],
            [Ipv4Address::class, '192.0.2.1', true],
            [Ipv6Address::class, '2001:db8::1', true],
            [Base64String::class, 'SGVsbG8=', true],
            [ClassString::class, 'stdClass', true],
        ];
    }
}
