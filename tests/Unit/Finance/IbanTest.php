<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Finance;

use MSSTC4PHP\ValueObject\Exception\InvalidIbanException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Finance\Iban;
use MSSTC4PHP\ValueObject\Support\IbanFormat;
use MSSTC4PHP\ValueObject\Support\IbanRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[UsesClass(InvalidValueObjectException::class)]
#[CoversClass(Iban::class)]
#[CoversClass(IbanFormat::class)]
#[CoversClass(IbanRegistry::class)]
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

    public function testRefusesAGermanIbanOfTwentyOneCharacters(): void
    {
        // DE89 3704 0044 0532 0130 00 without its last digit, re-checksummed: only the length is wrong.
        $this->expectException(InvalidIbanException::class);
        $this->expectExceptionMessage('does not match the registered format');

        Iban::from('DE51 3704 0044 0532 0130 0');
    }

    public function testRefusesALetterWhereTheRegistryAllowsOnlyDigits(): void
    {
        $this->expectException(InvalidIbanException::class);
        $this->expectExceptionMessage('does not match the registered format');

        Iban::from('GB82 WEST 1234 5698 7654 3A');
    }

    public function testAcceptsACountryAbsentFromTheRegistry(): void
    {
        // ZZ is not an ISO 3166 code, so no registry entry can ever exist; the checksum is valid (mod 97 = 1).
        $iban = Iban::from('ZZ44 ABCD 1234 5678');

        self::assertSame('ZZ44ABCD12345678', $iban->value);
        self::assertNull($iban->bankIdentifier());
        self::assertNull($iban->branchIdentifier());
        self::assertNull($iban->isSepaCountry());
    }

    public function testReadsBankAndBranchIdentifiers(): void
    {
        $iban = Iban::from('GB82 WEST 1234 5698 7654 32');

        self::assertSame('WEST', $iban->bankIdentifier());
        self::assertSame('123456', $iban->branchIdentifier());
        self::assertTrue($iban->isSepaCountry());
    }

    public function testBranchIdentifierIsNullWhereTheRegistryGivesNone(): void
    {
        $iban = Iban::from('DE89 3704 0044 0532 0130 00');

        self::assertSame('37040044', $iban->bankIdentifier());
        self::assertNull($iban->branchIdentifier());
    }

    public function testReportsANonSepaCountry(): void
    {
        self::assertFalse(Iban::from('AE07 0331 2345 6789 0123 456')->isSepaCountry());
    }

    /**
     * @return iterable<string, array{string, string, ?string, bool}>
     */
    public static function registryExamples(): iterable
    {
        /** @var array<string, array{string, string, ?string, bool}> $examples */
        $examples = require \dirname(__DIR__, 2) . '/Fixtures/iban-registry-examples.php';

        yield from $examples;
    }

    #[DataProvider('registryExamples')]
    public function testEveryRegistryExampleIsAcceptedWithItsIdentifiers(string $example, string $bank, ?string $branch, bool $sepa): void
    {
        $iban = Iban::from($example);

        self::assertSame($bank, $iban->bankIdentifier());
        self::assertSame($branch, $iban->branchIdentifier());
        self::assertSame($sepa, $iban->isSepaCountry());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function registryExampleIbans(): iterable
    {
        foreach (self::registryExamples() as $country => [$example]) {
            yield $country => [$example];
        }
    }

    #[DataProvider('registryExampleIbans')]
    public function testEveryRegistryExampleIsRefusedWhenOneCharacterShorter(string $example): void
    {
        $this->expectException(InvalidIbanException::class);

        Iban::from(\substr($example, 0, -1));
    }
}
