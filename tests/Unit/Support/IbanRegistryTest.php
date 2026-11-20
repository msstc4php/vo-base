<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use MSSTC4PHP\ValueObject\Support\IbanFormat;
use MSSTC4PHP\ValueObject\Support\IbanRegistry;
use MSSTC4PHP\ValueObject\Tests\Fixtures\IbanRegistryExamples;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IbanRegistry::class)]
#[CoversClass(IbanFormat::class)]
final class IbanRegistryTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string, ?string, bool}>
     */
    public static function countries(): iterable
    {
        foreach (IbanRegistryExamples::all() as $country => [$example, $bank, $branch, $sepa]) {
            yield $country => [$country, $example, $bank, $branch, $sepa];
        }
    }

    #[DataProvider('countries')]
    public function testHoldsTheFactsOfEveryRegisteredCountry(string $country, string $example, string $bank, ?string $branch, bool $sepa): void
    {
        $format = IbanRegistry::find($country);

        self::assertInstanceOf(IbanFormat::class, $format);
        self::assertTrue($format->accepts($example));
        self::assertFalse($format->accepts(\substr($example, 0, -1)));
        self::assertFalse($format->accepts($example . '0'));
        self::assertSame($bank, $format->bankIdentifier($example));
        self::assertSame($branch, $format->branchIdentifier($example));
        self::assertSame($sepa, $format->sepa);
    }

    public function testKnowsNothingOfACountryAbsentFromTheRegistry(): void
    {
        self::assertNull(IbanRegistry::find('US'));
        self::assertNull(IbanRegistry::find('US'));
    }

    public function testBuildsEachFormatOnce(): void
    {
        self::assertSame(IbanRegistry::find('DE'), IbanRegistry::find('DE'));
    }
}
