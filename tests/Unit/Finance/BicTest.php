<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Finance;

use MSSTC4PHP\ValueObject\Exception\InvalidBicException;
use MSSTC4PHP\ValueObject\Finance\Bic;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Bic::class)]
final class BicTest extends TestCase
{
    public function testStoresUppercase(): void
    {
        self::assertSame('DEUTDEFF', Bic::from('deutdeff')->value);
    }

    public function testExposesComponentsOfEightCharBic(): void
    {
        $bic = Bic::from('DEUTDEFF');

        self::assertSame('DEUT', $bic->bankCode());
        self::assertSame('DE', $bic->countryCode());
        self::assertSame('FF', $bic->locationCode());
        self::assertNull($bic->branchCode());
    }

    public function testExposesBranchCodeOfElevenCharBic(): void
    {
        $bic = Bic::from('DEUTDEFF500');

        self::assertSame('DEUT', $bic->bankCode());
        self::assertSame('DE', $bic->countryCode());
        self::assertSame('FF', $bic->locationCode());
        self::assertSame('500', $bic->branchCode());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidBics(): iterable
    {
        yield 'too short' => ['DEUTDE'];
        yield 'nine characters' => ['DEUTDEFF5'];
        yield 'digits in bank code' => ['1EUTDEFF'];
        yield 'illegal character' => ['DEUTDEF!'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidBics')]
    public function testRejectsInvalidBics(string $value): void
    {
        $this->expectException(InvalidBicException::class);

        Bic::from($value);
    }
}
