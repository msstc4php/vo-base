<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Tests\Unit\Primitive;

use MSSTC4PHP\ValueObject\Exception\InvalidNonEmptyStringException;
use MSSTC4PHP\ValueObject\Primitive\NonEmptyString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NonEmptyString::class)]
final class NonEmptyStringTest extends TestCase
{
    public function testBlankInputIsRejected(): void
    {
        self::assertNull(NonEmptyString::tryFrom(''));
        self::assertNull(NonEmptyString::tryFrom('   '));
    }

    public function testBlankInputThrowsTypedException(): void
    {
        $this->expectException(InvalidNonEmptyStringException::class);

        NonEmptyString::from('   ');
    }

    public function testNonEmptyValueIsPreservedVerbatim(): void
    {
        $string = NonEmptyString::from('  Hello  ');

        self::assertSame('  Hello  ', $string->value);
        self::assertSame('  Hello  ', (string) $string);
    }
}
