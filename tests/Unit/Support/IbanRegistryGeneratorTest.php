<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Support;

use MSSTC4PHP\ValueObject\Support\IbanFormat;
use MSSTC4PHP\ValueObject\Support\IbanRegistry;
use MSSTC4PHP\ValueObject\Tests\Support\IbanRegistryGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(IbanFormat::class)]
#[CoversClass(IbanRegistry::class)]
final class IbanRegistryGeneratorTest extends TestCase
{
    private const string EDITION = 'sample edition';

    public function testRendersTheSampleRegistryDeterministically(): void
    {
        $sample = $this->sample();
        $generator = new IbanRegistryGenerator();

        self::assertSame($generator->render($sample, self::EDITION), $generator->render($sample, self::EDITION));
    }

    public function testRendersCountriesInCodeOrderWithTheirFacts(): void
    {
        $output = new IbanRegistryGenerator()->render($this->sample(), self::EDITION);

        self::assertStringContainsString(' * Edition: sample edition' . "\n", $output);
        self::assertStringContainsString(' * Source SHA-256: ' . \hash('sha256', $this->sample()) . ".\n", $output);
        self::assertLessThan(\strpos($output, "'DE'"), \strpos($output, "'AD'"));
        self::assertLessThan(\strpos($output, "'FK'"), \strpos($output, "'DE'"));
        self::assertLessThan(\strpos($output, "'IT'"), \strpos($output, "'FK'"));
        self::assertStringContainsString(
            "'AD' => new IbanFormat(length: 24, bbanPattern: '/^[0-9]{4}[0-9]{4}[A-Z0-9]{12}\$/D', bankStart: 0, bankLength: 4, sepa: true, branchStart: 4, branchLength: 4),",
            $output,
        );
        self::assertStringContainsString(
            "'DE' => new IbanFormat(length: 22, bbanPattern: '/^[0-9]{8}[0-9]{10}\$/D', bankStart: 0, bankLength: 8, sepa: true),",
            $output,
        );
        self::assertStringContainsString("'FK' => new IbanFormat(length: 18, bbanPattern: '/^[A-Z]{2}[0-9]{12}\$/D', bankStart: 0, bankLength: 2, sepa: false),", $output);
        self::assertStringContainsString('bankStart: 1, bankLength: 5, sepa: true, branchStart: 6, branchLength: 5)', $output);
    }

    public function testNeutralisesACommentTerminatorInTheEdition(): void
    {
        $output = new IbanRegistryGenerator()->render($this->sample(), 'a */ b');

        self::assertStringContainsString('Edition: a * / b', $output);
    }

    public function testFormatWithHalfAGivenBranchPositionHasNoBranchIdentifier(): void
    {
        $withoutLength = new IbanFormat(length: 15, bbanPattern: '/^[0-9]{11}$/D', bankStart: 0, bankLength: 3, sepa: false, branchStart: 4);
        $withoutStart = new IbanFormat(length: 15, bbanPattern: '/^[0-9]{11}$/D', bankStart: 0, bankLength: 3, sepa: false, branchLength: 2);

        self::assertNull($withoutLength->branchIdentifier('XX0001234567890'));
        self::assertNull($withoutStart->branchIdentifier('XX0001234567890'));
    }

    /**
     * @return iterable<string, array{string, string, string}> row label, replacement for the DE cell, expected message
     */
    public static function brokenCells(): iterable
    {
        yield 'bad country code' => ['IBAN prefix country code (ISO 3166)', 'de', 'has no country code'];
        yield 'duplicate country' => ['IBAN prefix country code (ISO 3166)', 'AD', 'is listed twice'];
        yield 'bad structure' => ['BBAN structure', 'ab', 'unreadable BBAN structure'];
        yield 'structure length' => ['BBAN length', '17', 'is not 17 characters long'];
        yield 'iban length' => ['IBAN length', '30', 'does not fit BBAN length'];
        yield 'bank position' => ['Bank identifier position within the BBAN', 'x', 'unreadable identifier position'];
        yield 'position outside' => ['Bank identifier position within the BBAN', '1-19', 'lies outside the BBAN'];
        yield 'position reversed' => ['Bank identifier position within the BBAN', '4-2', 'lies outside the BBAN'];
        yield 'position from zero' => ['Bank identifier position within the BBAN', '0-2', 'lies outside the BBAN'];
        yield 'sepa flag' => ['SEPA country', 'Maybe', 'neither Yes nor No'];
        yield 'example' => ['IBAN electronic format example', 'DE89370400440532013XXX', 'does not fit the registry structure'];
        yield 'example of another country' => ['IBAN electronic format example', 'AT611904300234573201', 'does not fit the registry structure'];
    }

    #[DataProvider('brokenCells')]
    public function testRefusesABrokenCell(string $label, string $replacement, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        new IbanRegistryGenerator()->render($this->withGermanCell($label, $replacement), self::EDITION);
    }

    public function testRefusesARegistryWithoutACountryRow(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no "IBAN prefix country code (ISO 3166)" row');

        new IbanRegistryGenerator()->render("Name of country\tX\n", self::EDITION);
    }

    public function testCommittedRegistryKnowsTheGermanFormat(): void
    {
        $format = IbanRegistry::find('DE');

        self::assertInstanceOf(IbanFormat::class, $format);
        self::assertSame(22, $format->length);
        self::assertNull(IbanRegistry::find('ZZ'));
    }

    public function testFormatReadsIdentifiersAtItsPositions(): void
    {
        $format = new IbanFormat(length: 15, bbanPattern: '/^[0-9]{11}$/D', bankStart: 1, bankLength: 3, sepa: false, branchStart: 4, branchLength: 2);

        self::assertSame('123', $format->bankIdentifier('XX0001234567890'));
        self::assertSame('45', $format->branchIdentifier('XX0001234567890'));
        self::assertTrue($format->accepts('XX0001234567890'));
        self::assertFalse($format->accepts('XX000123456789'));
        self::assertFalse($format->accepts('XX000123456789A'));
    }

    private function withGermanCell(string $label, string $replacement): string
    {
        $lines = \preg_split('/\r?\n/', $this->sample());
        self::assertIsArray($lines);

        foreach ($lines as &$line) {
            if (\str_starts_with($line, $label . "\t")) {
                $cells = \explode("\t", $line);
                $cells[2] = $replacement;
                $line = \implode("\t", $cells);
            }
        }
        unset($line);

        return \implode("\n", $lines);
    }

    private function sample(): string
    {
        $sample = \file_get_contents(\dirname(__DIR__, 2) . '/Fixtures/iban-registry-sample.txt');
        self::assertIsString($sample);

        return $sample;
    }
}
