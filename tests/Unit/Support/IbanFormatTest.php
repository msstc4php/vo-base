<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use Closure;
use InvalidArgumentException;
use LogicException;
use MSSTC4PHP\ValueObject\Support\IbanFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IbanFormat::class)]
final class IbanFormatTest extends TestCase
{
    private const string IBAN = 'XX0001234567890';

    public function testReadsIdentifiersAtItsPositions(): void
    {
        $format = new IbanFormat(length: 15, bbanPattern: '/^[0-9]{11}$/D', bankStart: 1, bankLength: 3, sepa: false, branchStart: 4, branchLength: 2);

        self::assertSame('123', $format->bankIdentifier(self::IBAN));
        self::assertSame('45', $format->branchIdentifier(self::IBAN));
    }

    public function testHasNoBranchIdentifierWithoutBranchPosition(): void
    {
        $format = new IbanFormat(length: 15, bbanPattern: '/^[0-9]{11}$/D', bankStart: 0, bankLength: 3, sepa: true);

        self::assertNull($format->branchIdentifier(self::IBAN));
    }

    public function testAcceptsOnlyTheRegisteredLengthAndCharacters(): void
    {
        $format = new IbanFormat(length: 15, bbanPattern: '/^[0-9]{11}$/D', bankStart: 0, bankLength: 3, sepa: false);

        self::assertTrue($format->accepts(self::IBAN));
        self::assertFalse($format->accepts('XX000123456789'));
        self::assertFalse($format->accepts('XX000123456789A'));
    }

    public function testRefusesAnIbanTooShortForItsPosition(): void
    {
        $format = new IbanFormat(length: 15, bbanPattern: '/^[0-9]{11}$/D', bankStart: 20, bankLength: 3, sepa: false);

        $this->expectException(LogicException::class);

        $format->bankIdentifier(self::IBAN);
    }

    /**
     * @return iterable<string, array{Closure(): IbanFormat}> constructions that break the format's rules
     */
    public static function invalidFormats(): iterable
    {
        yield 'length below 15' => [static fn (): IbanFormat => new IbanFormat(length: 14, bbanPattern: '/x/', bankStart: 0, bankLength: 3, sepa: false)];
        yield 'length above 34' => [static fn (): IbanFormat => new IbanFormat(length: 35, bbanPattern: '/x/', bankStart: 0, bankLength: 3, sepa: false)];
        yield 'negative bank start' => [static fn (): IbanFormat => new IbanFormat(length: 15, bbanPattern: '/x/', bankStart: -1, bankLength: 3, sepa: false)];
        yield 'empty bank identifier' => [static fn (): IbanFormat => new IbanFormat(length: 15, bbanPattern: '/x/', bankStart: 0, bankLength: 0, sepa: false)];
        yield 'branch start without length' => [static fn (): IbanFormat => new IbanFormat(length: 15, bbanPattern: '/x/', bankStart: 0, bankLength: 3, sepa: false, branchStart: 4)];
        yield 'branch length without start' => [static fn (): IbanFormat => new IbanFormat(length: 15, bbanPattern: '/x/', bankStart: 0, bankLength: 3, sepa: false, branchLength: 2)];
        yield 'negative branch start' => [static fn (): IbanFormat => new IbanFormat(length: 15, bbanPattern: '/x/', bankStart: 0, bankLength: 3, sepa: false, branchStart: -1, branchLength: 2)];
        yield 'empty branch identifier' => [static fn (): IbanFormat => new IbanFormat(length: 15, bbanPattern: '/x/', bankStart: 0, bankLength: 3, sepa: false, branchStart: 4, branchLength: 0)];
        yield 'empty pattern' => [static fn (): IbanFormat => new IbanFormat(length: 15, bbanPattern: '', bankStart: 0, bankLength: 3, sepa: false)];
    }

    /**
     * @param Closure(): IbanFormat $construct
     */
    #[DataProvider('invalidFormats')]
    public function testRefusesInvalidArguments(Closure $construct): void
    {
        $this->expectException(InvalidArgumentException::class);

        $construct();
    }

    public function testAcceptsTheLimits(): void
    {
        $short = new IbanFormat(length: 15, bbanPattern: '/x/', bankStart: 0, bankLength: 1, sepa: false, branchStart: 0, branchLength: 1);
        $long = new IbanFormat(length: 34, bbanPattern: '/x/', bankStart: 0, bankLength: 1, sepa: false);

        self::assertSame(15, $short->length);
        self::assertSame(34, $long->length);
    }
}
