<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Finance;

use MSSTC4PHP\ValueObject\Exception\InvalidIbanException;
use MSSTC4PHP\ValueObject\Finance\Iban;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Iban::class)]
final class IbanTest extends TestCase
{
    public function testNormalizesToCompactUppercaseForm(): void
    {
        $iban = Iban::from('gb82 west 1234 5698 7654 32');

        self::assertSame('GB82WEST12345698765432', $iban->value);
    }

    public function testExposesCountryAndCheckDigits(): void
    {
        $iban = Iban::from('GB82 WEST 1234 5698 7654 32');

        self::assertSame('GB', $iban->countryCode());
        self::assertSame('82', $iban->checkDigits());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validIbans(): iterable
    {
        yield 'United Kingdom' => ['GB82 WEST 1234 5698 7654 32'];
        yield 'Germany' => ['DE89 3704 0044 0532 0130 00'];
        yield 'France' => ['FR14 2004 1010 0505 0001 3M02 606'];
    }

    #[DataProvider('validIbans')]
    public function testAcceptsValidIbans(string $value): void
    {
        self::assertTrue(Iban::isValid($value));
    }

    public function testRejectsWrongChecksum(): void
    {
        $this->expectException(InvalidIbanException::class);

        Iban::from('GB82 WEST 1234 5698 7654 33');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIbans(): iterable
    {
        yield 'too short' => ['GB82 W'];
        yield 'bad country code' => ['1282 WEST 1234 5698 7654 32'];
        yield 'letters in check digits' => ['GBXX WEST 1234 5698 7654 32'];
        yield 'illegal character' => ['GB82 WEST 1234 5698 7654 3*'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidIbans')]
    public function testRejectsInvalidIbans(string $value): void
    {
        $this->expectException(InvalidIbanException::class);

        Iban::from($value);
    }
}
