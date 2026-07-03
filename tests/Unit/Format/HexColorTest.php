<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Format;

use MSSTC4PHP\ValueObject\Exception\InvalidHexColorException;
use MSSTC4PHP\ValueObject\Format\HexColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HexColor::class)]
final class HexColorTest extends TestCase
{
    public function testAcceptsLongForm(): void
    {
        self::assertSame('#aabbcc', HexColor::from('#aabbcc')->value);
    }

    public function testNormalizesShortFormToLowercase(): void
    {
        self::assertSame('#abc', HexColor::from('#ABC')->value);
    }

    public function testRejectsNonHexDigits(): void
    {
        $this->expectException(InvalidHexColorException::class);

        HexColor::from('#gg0011');
    }

    public function testRejectsMissingHash(): void
    {
        $this->expectException(InvalidHexColorException::class);

        HexColor::from('aabbcc');
    }
}
