<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Exception;

use Closure;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Finance\CreditCardNumber;
use MSSTC4PHP\ValueObject\Finance\Iban;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(InvalidValueObjectException::class)]
#[CoversClass(CreditCardNumber::class)]
#[CoversClass(Iban::class)]
#[CoversClass(CountryCode::class)]
final class InvalidValueObjectExceptionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function excerptProvider(): iterable
    {
        yield 'plain' => ['abc', 'abc'];
        yield 'crlf' => ["a\r\nb", 'a\r\nb'];
        yield 'nul and del' => ["\0\x7F", '\000\177'];
        yield 'backslash stays distinguishable' => ['a\nb', 'a\\\\nb'];
        yield 'valid utf-8 stays readable' => ['ok é', 'ok é'];
        yield 'only stray bytes are hex' => ["ok é \xFF", 'ok é \xFF'];
        yield 'lone continuation byte' => ["\x80", '\x80'];
        yield 'overlong two-byte' => ["\xC0\xAF", '\xC0\xAF'];
        yield 'overlong three-byte' => ["\xE0\x80\xAF", '\xE0\x80\xAF'];
        yield 'surrogate' => ["\xED\xA0\x80", '\xED\xA0\x80'];
        yield 'above U+10FFFF' => ["\xF4\x90\x80\x80", '\xF4\x90\x80\x80'];
        yield 'invalid lead byte' => ["\xF5", '\xF5'];
        yield 'lead byte then ascii' => ["\xC3(", '\xC3('];
        yield 'truncated sequence' => ["\xE2\x82", '\xE2\x82'];
        yield 'four-byte emoji stays' => ["\xF0\x9F\x98\x80", "\xF0\x9F\x98\x80"];
        yield 'highest code point stays' => ["\xF4\x8F\xBF\xBF", "\xF4\x8F\xBF\xBF"];
        yield 'literal backslash-x is not confused' => ['\xFF', '\\\\xFF'];
    }

    #[DataProvider('excerptProvider')]
    public function testExcerpt(string $input, string $expected): void
    {
        self::assertSame($expected, InvalidValueObjectException::excerpt($input));
    }

    public function testLongInputIsCutAtACharacterBoundary(): void
    {
        $excerpt = InvalidValueObjectException::excerpt(\str_repeat('а', 150));

        self::assertSame(\str_repeat('а', 100) . '...', $excerpt);
        self::assertMatchesRegularExpression('//u', $excerpt);
    }

    public function testCutDoesNotRunAwayOnContinuationBytes(): void
    {
        self::assertStringStartsWith('\x80\x80', InvalidValueObjectException::excerpt(\str_repeat("\x80", 300)));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function cardProvider(): iterable
    {
        yield 'luhn typo' => ['4111111111111112'];
        yield 'with a trailing cvv' => ['4111 1111 1111 1111 123'];
        yield 'with an expiry date' => ['4111111111111111-12/29'];
    }

    #[DataProvider('cardProvider')]
    public function testCardMessagesCarryNoDigit(string $input): void
    {
        $message = $this->messageOf(static fn (): CreditCardNumber => CreditCardNumber::from($input));

        self::assertSame(\sprintf('Invalid credit card number (%d characters).', \strlen($input)), $message);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function ibanProvider(): iterable
    {
        yield 'checksum failure keeps country and last four' => ['DE89370400440532013001', 'IBAN DE****************3001 fails the ISO 7064 checksum.'];
        yield 'too short is fully hidden' => ['1234567', 'Invalid IBAN (7 characters).'];
        yield 'odd characters are fully hidden' => ['DE89 3704 0044 0532 0130 0!', 'Invalid IBAN (27 characters).'];
        yield 'too long is fully hidden' => [\str_repeat('A', 5000), 'Invalid IBAN (5000 characters).'];
        yield 'a card number typed into the iban field' => ['4111111111111111', 'Invalid IBAN (16 characters).'];
    }

    #[DataProvider('ibanProvider')]
    public function testIbanMessagesAreMasked(string $input, string $expected): void
    {
        self::assertSame($expected, $this->messageOf(static fn (): Iban => Iban::from($input)));
    }

    public function testCountryCodeLookupsEscapeTheirInput(): void
    {
        self::assertSame(
            'Unknown ISO 3166-1 numeric code "1\r\n".',
            $this->messageOf(static fn (): CountryCode => CountryCode::fromNumeric("1\r\n")),
        );
    }

    /**
     * @param Closure(): (CreditCardNumber|Iban|CountryCode) $construct
     */
    private function messageOf(Closure $construct): string
    {
        try {
            $construct();
        } catch (Throwable $exception) {
            return $exception->getMessage();
        }

        self::fail('expected an exception');
    }
}
