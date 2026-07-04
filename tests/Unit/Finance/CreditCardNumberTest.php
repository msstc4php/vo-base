<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Finance;

use MSSTC4PHP\ValueObject\Exception\InvalidCreditCardNumberException;
use MSSTC4PHP\ValueObject\Finance\CreditCardNumber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreditCardNumber::class)]
final class CreditCardNumberTest extends TestCase
{
    public function testStripsSeparatorsAndStoresDigits(): void
    {
        self::assertSame('4111111111111111', CreditCardNumber::from('4111-1111 1111-1111')->value);
    }

    public function testExposesLast4AndVisaBrand(): void
    {
        $card = CreditCardNumber::from('4111 1111 1111 1111');

        self::assertSame('1111', $card->last4());
        self::assertSame('visa', $card->brand());
    }

    /**
     * @return iterable<string, array{string, 'visa'|'mastercard'|'amex'|'discover'|'unknown'}>
     */
    public static function brands(): iterable
    {
        yield 'visa' => ['4111111111111111', 'visa'];
        yield 'mastercard 5x' => ['5500005555555559', 'mastercard'];
        yield 'mastercard 2x' => ['2223000048410010', 'mastercard'];
        yield 'amex' => ['378282246310005', 'amex'];
        yield 'discover' => ['6011111111111117', 'discover'];
        yield 'unknown' => ['8888888888888888', 'unknown'];
    }

    #[DataProvider('brands')]
    public function testDetectsBrand(string $number, string $expected): void
    {
        self::assertSame($expected, CreditCardNumber::from($number)->brand());
    }

    public function testRejectsLuhnInvalidNumber(): void
    {
        $this->expectException(InvalidCreditCardNumberException::class);

        CreditCardNumber::from('4111 1111 1111 1112');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNumbers(): iterable
    {
        yield 'too short' => ['4111111111'];
        yield 'too long' => ['41111111111111111111'];
        yield 'non-digit' => ['4111 1111 1111 111a'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidNumbers')]
    public function testRejectsInvalidNumbers(string $value): void
    {
        $this->expectException(InvalidCreditCardNumberException::class);

        CreditCardNumber::from($value);
    }
}
